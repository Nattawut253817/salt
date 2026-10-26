<?php

namespace App\Http\Middleware;

use App\Services\OidcClient;
use App\Services\OidcException;
use App\Services\OidcTokenResponse;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * ODPCX IdP — OIDC Token Validation Middleware
 *
 * Protects routes that require a valid OIDC access token.
 *
 * Flow per request:
 *   1. Read access token + expiry from session.
 *   2. If token is expired (or missing) → single-flight refresh (Rule 3).
 *   3. If proactive-refresh window → schedule a background refresh (Rule 5 jitter).
 *   4. Pass to next middleware; if downstream call gets 401 from userinfo,
 *      the OidcClient parser raises OidcException which is handled here.
 *
 * Fatal-error handling (Rule 2):
 *   On any FATAL OidcException: purge all tokens + redirect to /authorize.
 *
 * Rate-limit handling (Rule 5):
 *   On TYPE_RATE_LIMITED: return 503 (if API) or show a waiting page (if web).
 *   Do NOT purge the session on 429.
 */
class OidcAuthenticated
{
    /** Cache-lock key prefix for single-flight refresh, keyed by session ID. */
    private const REFRESH_LOCK_PREFIX = 'oidc:refresh:';

    public function __construct(private readonly OidcClient $oidc) {}

    public function handle(Request $request, Closure $next): Response
    {
        $accessToken  = $request->session()->get(OidcClient::SK_ACCESS_TOKEN);
        $refreshToken = $request->session()->get(OidcClient::SK_REFRESH_TOKEN);
        $expiresAt    = (int) $request->session()->get(OidcClient::SK_AT_EXPIRES_AT, 0);
        $expiresIn    = (int) $request->session()->get(OidcClient::SK_AT_EXPIRES_IN, 900);

        // ── No access token at all → redirect to /authorize ──────────────
        if (empty($accessToken)) {
            $request->session()->put('url.intended', $request->url());
            return redirect()->route('oidc.redirect');
        }

        // ── Token expired → single-flight refresh (Rule 3) ───────────────
        $tokenExpired = time() >= ($expiresAt - 30);   // 30 s buffer

        if ($tokenExpired) {
            if (empty($refreshToken)) {
                return $this->forceReauth($request, 'Access token expired, no refresh token');
            }

            try {
                $newTokens = $this->doSingleFlightRefresh($request, $refreshToken);
                $accessToken = $newTokens->accessToken;
            } catch (OidcException $e) {
                return $this->handleRefreshException($request, $e);
            }
        }

        // ── Proactive refresh (Rule 5 jitter): 80–90 % of lifetime ───────
        // Non-blocking: only trigger if not already refreshing
        if (! $tokenExpired && $this->shouldProactivelyRefresh($expiresAt, $expiresIn)) {
            $lockKey = self::REFRESH_LOCK_PREFIX . $request->session()->getId() . ':proactive';
            if (Cache::add($lockKey, 1, 60)) {
                // Proactive refresh in current request to avoid race on next hit
                try {
                    if (! empty($refreshToken)) {
                        $newTokens   = $this->oidc->refreshAccessToken($refreshToken);
                        $accessToken = $newTokens->accessToken;
                        $this->storeTokensInSession($request, $newTokens);
                    }
                } catch (OidcException $e) {
                    Cache::forget($lockKey);
                    if ($e->isFatal()) {
                        return $this->handleRefreshException($request, $e);
                    }
                    // Non-fatal (transient / rate-limit): let the current token be used,
                    // next request will retry
                    Log::warning('OIDC: proactive refresh failed (non-fatal)', [
                        'type'  => $e->getErrorType(),
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        // ── Claims cache validity check (Rule 3: no cache > 15 min) ──────
        $claimsCachedAt = (int) $request->session()->get(OidcClient::SK_CLAIMS_CACHED_AT, 0);
        $maxClaimAge    = config('oidc.access_token_ttl', 900);

        if ($claimsCachedAt && (time() - $claimsCachedAt) >= $maxClaimAge) {
            // Claims too old — refresh them from userinfo
            try {
                $claims = $this->oidc->getUserInfo($accessToken);
                $request->session()->put(OidcClient::SK_CLAIMS,          $claims);
                $request->session()->put(OidcClient::SK_CLAIMS_CACHED_AT, time());
            } catch (OidcException $e) {
                // TYPE_FATAL here is "invalid_token: ..." from Rule 3 parser #2
                if ($e->isFatal()) {
                    // Rule 3: refresh once, retry userinfo
                    try {
                        if (empty($refreshToken)) {
                            throw $e;   // no refresh token → fatal
                        }
                        $newTokens  = $this->doSingleFlightRefresh($request, $refreshToken);
                        $accessToken = $newTokens->accessToken;

                        // Retry userinfo exactly ONCE
                        $claims = $this->oidc->getUserInfo($accessToken);
                        $request->session()->put(OidcClient::SK_CLAIMS,          $claims);
                        $request->session()->put(OidcClient::SK_CLAIMS_CACHED_AT, time());

                    } catch (OidcException $inner) {
                        // Refresh failed (FATAL), or retried userinfo still 401 → re-auth
                        return $this->handleRefreshException($request, $inner);
                    }
                }
                // Transient: continue with stale claims rather than breaking UX
            }
        }

        return $next($request);
    }

    /* ------------------------------------------------------------------ */
    /* Single-flight refresh (Rule 3 — concurrent requests share one call)  */
    /* ------------------------------------------------------------------ */

    /**
     * Acquire a per-session lock; if another coroutine already holds it,
     * wait briefly then re-read the (now-refreshed) token from session.
     *
     * @throws OidcException
     */
    private function doSingleFlightRefresh(Request $request, string $refreshToken): OidcTokenResponse
    {
        $lockTtl = config('oidc.refresh_lock_ttl', 30);
        $lockKey = self::REFRESH_LOCK_PREFIX . $request->session()->getId();

        $lock = Cache::lock($lockKey, $lockTtl);

        if ($lock->get()) {
            // We hold the lock — perform the actual refresh
            try {
                $newTokens = $this->oidc->refreshAccessToken($refreshToken);
                $this->storeTokensInSession($request, $newTokens);
                return $newTokens;
            } finally {
                $lock->release();
            }
        }

        // Another concurrent request holds the lock — wait for it, then re-read
        $acquired = $lock->block(10);   // wait up to 10 s
        if ($acquired) {
            $lock->release();
        }

        // Re-read refreshed token from session
        $freshAt = $request->session()->get(OidcClient::SK_ACCESS_TOKEN);
        $freshIn = (int) $request->session()->get(OidcClient::SK_AT_EXPIRES_IN, 900);
        $freshExpires = (int) $request->session()->get(OidcClient::SK_AT_EXPIRES_AT, 0);
        $freshRt = $request->session()->get(OidcClient::SK_REFRESH_TOKEN, $refreshToken);

        return new OidcTokenResponse(
            accessToken:  $freshAt ?? $refreshToken,  // fallback
            refreshToken: $freshRt,
            expiresIn:    $freshIn,
            expiresAt:    $freshExpires,
        );
    }

    /* ------------------------------------------------------------------ */
    /* Helpers                                                              */
    /* ------------------------------------------------------------------ */

    private function shouldProactivelyRefresh(int $expiresAt, int $expiresIn): bool
    {
        if ($expiresIn <= 0) return false;
        $age          = time() - ($expiresAt - $expiresIn);
        $minPct       = config('oidc.proactive_refresh_min_pct', 80);
        $maxPct       = config('oidc.proactive_refresh_max_pct', 90);
        $thresholdSecs = (int) ($expiresIn * rand($minPct, $maxPct) / 100);
        return $age >= $thresholdSecs;
    }

    private function storeTokensInSession(Request $request, OidcTokenResponse $tokens): void
    {
        $request->session()->put(OidcClient::SK_ACCESS_TOKEN,  $tokens->accessToken);
        $request->session()->put(OidcClient::SK_REFRESH_TOKEN, $tokens->refreshToken);
        $request->session()->put(OidcClient::SK_AT_EXPIRES_AT, $tokens->expiresAt);
        $request->session()->put(OidcClient::SK_AT_EXPIRES_IN, $tokens->expiresIn);
    }

    /**
     * Handle an OidcException thrown during refresh.
     *
     * Rule 2 (FATAL): purge + redirect to /authorize.
     * Rule 5 (rate-limited): do NOT purge; show error or 503.
     * Transient: show error.
     */
    private function handleRefreshException(Request $request, OidcException $e): Response
    {
        if ($e->isFatal()) {
            Log::warning('OIDC: fatal error during refresh — purging session', [
                'oauth_error' => $e->getOauthError(),
                'description' => $e->getErrorDescription(),
            ]);
            $this->purgeOidcSession($request);
            return redirect()->route('oidc.redirect');
        }

        if ($e->isRateLimited()) {
            // Rule 5: 429 is NOT fatal — do NOT purge session
            Log::warning('OIDC: rate-limited during refresh', [
                'retry_after' => $e->getRetryAfterSecs(),
            ]);
            if ($request->expectsJson()) {
                return response()->json([
                    'error'       => 'too_many_requests',
                    'retry_after' => $e->getRetryAfterSecs(),
                ], 503);
            }
            return response()->view('errors.oidc-rate-limited', [
                'retryAfter' => $e->getRetryAfterSecs(),
            ], 503);
        }

        // Transient error
        Log::error('OIDC: transient error during refresh', ['error' => $e->getMessage()]);
        if ($request->expectsJson()) {
            return response()->json(['error' => 'authentication_error'], 500);
        }
        return redirect()->route('login')->withErrors(['oidc' => 'Authentication error. Please try again.']);
    }

    private function forceReauth(Request $request, string $reason): Response
    {
        Log::warning('OIDC middleware: forcing re-authentication', ['reason' => $reason]);
        $this->purgeOidcSession($request);
        $request->session()->put('url.intended', $request->url());
        return redirect()->route('oidc.redirect');
    }

    private function purgeOidcSession(Request $request): void
    {
        $keys = [
            OidcClient::SK_ACCESS_TOKEN,
            OidcClient::SK_REFRESH_TOKEN,
            OidcClient::SK_AT_EXPIRES_AT,
            OidcClient::SK_AT_EXPIRES_IN,
            OidcClient::SK_ID_TOKEN,
            OidcClient::SK_CLAIMS,
            OidcClient::SK_CLAIMS_CACHED_AT,
            OidcClient::SK_OAUTH_STATE,
            OidcClient::SK_OAUTH_NONCE,
            OidcClient::SK_PKCE_VERIFIER,
        ];

        foreach ($keys as $key) {
            $request->session()->forget($key);
        }
    }
}
