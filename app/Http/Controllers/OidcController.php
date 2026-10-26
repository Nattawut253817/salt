<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\OidcClient;
use App\Services\OidcException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * ODPCX IdP — OAuth 2.0 / OIDC Authorization Code Flow
 *
 * GET  /oauth/redirect  → buildAuthorizeUrl() → redirect to IdP
 * GET  /oauth/callback  → exchange code, store tokens, redirect to /
 *
 * UNCHANGED (per brief): /authorize params, PKCE, redirect_uri matching,
 *                        state/nonce validation, scopes (cid, mfa).
 */
class OidcController extends Controller
{
    public function __construct(private readonly OidcClient $oidc) {}

    /* ------------------------------------------------------------------ */
    /* Step 1 — Redirect to IdP /authorize                                  */
    /* ------------------------------------------------------------------ */

    public function redirect(Request $request): RedirectResponse
    {
        // Generate PKCE pair
        $pkce = $this->oidc->generatePkce();

        // Generate state + nonce (cryptographically secure)
        $state = Str::random(40);
        $nonce = Str::random(40);

        // Store in session for validation at callback (DO NOT TOUCH this set)
        $request->session()->put(OidcClient::SK_PKCE_VERIFIER, $pkce['verifier']);
        $request->session()->put(OidcClient::SK_OAUTH_STATE,   $state);
        $request->session()->put(OidcClient::SK_OAUTH_NONCE,   $nonce);

        $url = $this->oidc->buildAuthorizeUrl($state, $nonce, $pkce['challenge']);

        return redirect()->away($url);
    }

    /* ------------------------------------------------------------------ */
    /* Step 2 — Callback: validate state/nonce, exchange code, store tokens */
    /* ------------------------------------------------------------------ */

    public function callback(Request $request): RedirectResponse
    {
        // ── Error from IdP ───────────────────────────────────────────────
        if ($request->has('error')) {
            Log::warning('OIDC callback: IdP returned error', [
                'error'             => $request->input('error'),
                'error_description' => $request->input('error_description'),
            ]);
            return redirect()->route('login')->withErrors([
                'oidc' => 'Authentication failed: ' . $request->input('error_description', 'unknown error'),
            ]);
        }

        // ── state validation (UNCHANGED — must not be modified) ──────────
        $returnedState  = $request->input('state', '');
        $expectedState  = $request->session()->pull(OidcClient::SK_OAUTH_STATE, '');

        if (! hash_equals($expectedState, $returnedState) || empty($returnedState)) {
            Log::warning('OIDC callback: state mismatch', [
                'expected' => substr($expectedState, 0, 8) . '...',
                'returned' => substr($returnedState,  0, 8) . '...',
            ]);
            return $this->forceReauth($request, 'State validation failed.');
        }

        // ── Retrieve PKCE verifier and nonce ─────────────────────────────
        $codeVerifier = $request->session()->pull(OidcClient::SK_PKCE_VERIFIER, '');
        $nonce        = $request->session()->pull(OidcClient::SK_OAUTH_NONCE,   '');

        if (empty($codeVerifier)) {
            return $this->forceReauth($request, 'PKCE verifier missing.');
        }

        $code = $request->input('code', '');
        if (empty($code)) {
            return $this->forceReauth($request, 'Authorization code missing.');
        }

        // ── Exchange code for tokens (Rule 1 — sends client_secret) ──────
        try {
            $tokens = $this->oidc->exchangeCode($code, $codeVerifier);
        } catch (OidcException $e) {
            Log::error('OIDC callback: code exchange failed', [
                'type'  => $e->getErrorType(),
                'error' => $e->getMessage(),
            ]);

            if ($e->isFatal()) {
                // Rule 2: purge + re-auth
                $this->purgeOidcSession($request);
                return redirect()->route('oidc.redirect');
            }

            return redirect()->route('login')->withErrors([
                'oidc' => 'Token exchange failed. Please try again.',
            ]);
        }

        // ── Store tokens in encrypted session ────────────────────────────
        $request->session()->put(OidcClient::SK_ACCESS_TOKEN,  $tokens->accessToken);
        $request->session()->put(OidcClient::SK_REFRESH_TOKEN, $tokens->refreshToken);
        $request->session()->put(OidcClient::SK_AT_EXPIRES_AT, $tokens->expiresAt);
        $request->session()->put(OidcClient::SK_AT_EXPIRES_IN, $tokens->expiresIn);

        if ($tokens->idToken) {
            $request->session()->put(OidcClient::SK_ID_TOKEN, $tokens->idToken);
        }

        // ── Fetch userinfo and validate nonce (Rule 3 parser #2) ─────────
        try {
            $claims = $this->oidc->getUserInfo($tokens->accessToken);

            // nonce validation (UNCHANGED)
            if (! empty($nonce) && ($claims['nonce'] ?? '') !== $nonce) {
                Log::warning('OIDC callback: nonce mismatch');
                $this->purgeOidcSession($request);
                return redirect()->route('oidc.redirect');
            }

            $request->session()->put(OidcClient::SK_CLAIMS,          $claims);
            $request->session()->put(OidcClient::SK_CLAIMS_CACHED_AT, time());

        } catch (OidcException $e) {
            // Userinfo 401 right after a fresh code exchange is unusual but handle it
            Log::warning('OIDC callback: userinfo failed after fresh exchange', [
                'error' => $e->getMessage(),
            ]);
            // Non-blocking: proceed without claims (middleware will retry on next request)
        }

        // -- Look up local user by IdP email and call Auth::login() ---------
        // After 2026-11-01 all admin routes require valid OIDC tokens.
        // Auth::login() lets existing 'auth' middleware coexist with oidc.auth.
        $idpEmail = strtolower(trim(
            $claims['email'] ?? $claims['preferred_username'] ?? ''
        ));

        if (! empty($idpEmail)) {
            $localUser = User::where('email', $idpEmail)->first();

            if ($localUser && $localUser->is_approved) {
                Auth::login($localUser, remember: false);
                $request->session()->regenerate();
            } else {
                $this->purgeOidcSession($request);
                $errorMsg = $localUser
                    ? 'บัญชีของคุณยังรอการอนุมัติจากผู้ดูแลระบบ'
                    : 'ไม่พบบัญชีสำหรับอีเมล ' . $idpEmail . ' ในระบบ กรุณาติดต่อผู้ดูแล';
                return redirect()->route('login')->withErrors(['oidc' => $errorMsg]);
            }
        }

        // ── Redirect to originally requested page ─────────────────────────
        $intended = $request->session()->pull('url.intended', route('home'));

        return redirect()->to($intended);
    }

    /* ------------------------------------------------------------------ */
    /* Logout (Rule 4)                                                      */
    /* ------------------------------------------------------------------ */

    /**
     * Log out the user.
     *
     * Rule 4:
     *   1. Purge local session state FIRST (always, regardless of IdP response).
     *   2. THEN call /revoke — fire-and-forget; never treat 200 as proof.
     *   3. Only revoke tokens issued to THIS app's client_id.
     */
    public function logout(Request $request): RedirectResponse
    {
        // Collect tokens before purging
        $accessToken  = $request->session()->get(OidcClient::SK_ACCESS_TOKEN);
        $refreshToken = $request->session()->get(OidcClient::SK_REFRESH_TOKEN);

        // ── Rule 4 Step 1: purge local state immediately ─────────────────
        $this->purgeOidcSession($request);
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // ── Rule 4 Step 2: best-effort revocation (fire and forget) ──────
        if ($accessToken) {
            $this->oidc->revokeToken($accessToken, 'access_token');
        }
        if ($refreshToken) {
            $this->oidc->revokeToken($refreshToken, 'refresh_token');
        }
        // Rule 4: do NOT check revoke response — 200 is not proof of revocation.

        return redirect()->route('login');
    }

    /* ------------------------------------------------------------------ */
    /* Helpers                                                              */
    /* ------------------------------------------------------------------ */

    /** Force a fresh /authorize cycle (Rule 2 / security). */
    private function forceReauth(Request $request, string $reason): RedirectResponse
    {
        Log::warning('OIDC: forcing re-authentication', ['reason' => $reason]);
        $this->purgeOidcSession($request);
        return redirect()->route('oidc.redirect');
    }

    /**
     * Purge ALL OIDC token and state data from the session.
     * Called on fatal errors (Rule 2) and logout (Rule 4).
     */
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
