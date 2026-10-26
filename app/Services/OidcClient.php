<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * ODPCX IdP — OAuth 2.0 / OIDC Confidential Client
 *
 * Enforcement date: 2026-11-01.
 *
 * Rules implemented:
 *   Rule 1  — refresh_token grant always sends client_secret (confidential)
 *   Rule 2  — fatal errors: purge + re-auth, no retry
 *   Rule 3  — /userinfo 401 → refresh once (single-flight) → retry once
 *   Rule 4  — revocation always purges local state regardless of IdP 200
 *   Rule 5  — 429 is NOT fatal; global gate paused for Retry-After; jittered backoff
 *
 * Error parsers:
 *   parseTokenEndpointError()  — for the token endpoint (OAuth style body)
 *   parseUserInfoError()       — for the userinfo endpoint (NestJS style body)
 *   These are intentionally SEPARATE and MUST NOT be merged.
 *
 * Endpoint discovery:
 *   All IdP endpoint URLs are resolved dynamically via OIDC Discovery
 *   ({issuer}/.well-known/openid-configuration) and cached.
 *   NO paths are hardcoded — the IdP advertises its own endpoint URLs.
 */
class OidcClient
{
    /* ------------------------------------------------------------------ */
    /* Discovery cache key                                                  */
    /* ------------------------------------------------------------------ */
    private const DISCOVERY_CACHE_KEY = 'oidc:discovery';

    /* ------------------------------------------------------------------ */
    /* Session storage keys                                                 */
    /* ------------------------------------------------------------------ */
    public const SK_ACCESS_TOKEN      = 'oidc.access_token';
    public const SK_REFRESH_TOKEN     = 'oidc.refresh_token';
    public const SK_AT_EXPIRES_AT     = 'oidc.at_expires_at';
    public const SK_AT_EXPIRES_IN     = 'oidc.at_expires_in';
    public const SK_ID_TOKEN          = 'oidc.id_token';
    public const SK_CLAIMS            = 'oidc.claims';
    public const SK_CLAIMS_CACHED_AT  = 'oidc.claims_cached_at';
    public const SK_OAUTH_STATE       = 'oidc.state';
    public const SK_OAUTH_NONCE       = 'oidc.nonce';
    public const SK_PKCE_VERIFIER     = 'oidc.pkce_verifier';

    /* ------------------------------------------------------------------ */
    /* Constructor                                                          */
    /* ------------------------------------------------------------------ */
    public function __construct(
        private readonly string $idpBase,
        private readonly string $clientId,
        private readonly string $clientSecret,
        private readonly string $redirectUri,
        private readonly array  $scopes = ['openid', 'profile', 'email', 'cid', 'mfa'],
    ) {}

    /* ================================================================== */
    /* OIDC Discovery                                                       */
    /* ================================================================== */

    /**
     * Fetch (and cache) the OIDC Discovery document.
     *
     * URL: {idpBase}/.well-known/openid-configuration
     *
     * Returns the full document array. Failed fetches are NEVER cached so
     * that the very next request retries automatically once the IdP recovers.
     *
     * Keys we use:
     *   authorization_endpoint — used by buildAuthorizeUrl()
     *   token_endpoint         — used by callTokenEndpoint()
     *   userinfo_endpoint      — used by getUserInfo()
     *   introspection_endpoint — used by introspect()
     *   revocation_endpoint    — used by revokeToken()
     *
     * @throws OidcException TYPE_TRANSIENT when the IdP is unreachable
     */
    public function discovery(): array
    {
        $cached = Cache::get(self::DISCOVERY_CACHE_KEY);
        if ($cached !== null) {
            return $cached;
        }

        $url = rtrim($this->idpBase, '/') . '/.well-known/openid-configuration';

        try {
            $response = Http::timeout(10)->get($url);
        } catch (\Throwable $e) {
            throw new OidcException(
                'OIDC discovery fetch failed: ' . $e->getMessage(),
                OidcException::TYPE_TRANSIENT,
                previous: $e,
            );
        }

        if (! $response->successful()) {
            throw new OidcException(
                "OIDC discovery returned HTTP {$response->status()} — IdP may be down",
                OidcException::TYPE_TRANSIENT,
            );
        }

        $document = $response->json();
        $ttl = config('oidc.discovery_ttl', 3600);
        Cache::put(self::DISCOVERY_CACHE_KEY, $document, $ttl);

        Log::info('OIDC: discovery document loaded', [
            'authorization_endpoint' => $document['authorization_endpoint'] ?? 'missing',
            'token_endpoint'         => $document['token_endpoint']         ?? 'missing',
        ]);

        return $document;
    }

    /* ================================================================== */
    /* PKCE helpers                                                         */
    /* ================================================================== */

    /**
     * Generate a PKCE pair (S256).
     * Returns ['verifier' => ..., 'challenge' => ..., 'method' => 'S256'].
     */
    public function generatePkce(): array
    {
        $verifier  = Str::random(96);                        // 96 chars → ~128 bits entropy
        $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');

        return [
            'verifier'  => $verifier,
            'challenge' => $challenge,
            'method'    => 'S256',
        ];
    }

    /* ================================================================== */
    /* Authorization URL                                                    */
    /* ================================================================== */

    /**
     * Build the authorization redirect URL using the discovered endpoint.
     *
     * @throws OidcException  TYPE_TRANSIENT when discovery fails
     */
    public function buildAuthorizeUrl(string $state, string $nonce, string $codeChallenge): string
    {
        $document = $this->discovery();

        $authorizeEndpoint = $document['authorization_endpoint']
            ?? throw new OidcException(
                'OIDC discovery: missing authorization_endpoint',
                OidcException::TYPE_TRANSIENT,
            );

        $params = http_build_query([
            'response_type'         => 'code',
            'client_id'             => $this->clientId,
            'redirect_uri'          => $this->redirectUri,
            'scope'                 => implode(' ', $this->scopes),
            'state'                 => $state,
            'nonce'                 => $nonce,
            'code_challenge'        => $codeChallenge,
            'code_challenge_method' => 'S256',
        ]);

        return $authorizeEndpoint . '?' . $params;
    }

    /* ================================================================== */
    /* Token: authorization-code exchange                                   */
    /* ================================================================== */

    /**
     * Exchange an authorization code for tokens (Rule 1 — confidential: include secret).
     *
     * @throws OidcException
     */
    public function exchangeCode(string $code, string $codeVerifier): OidcTokenResponse
    {
        $body = $this->callTokenEndpoint([
            'grant_type'    => 'authorization_code',
            'code'          => $code,
            'redirect_uri'  => $this->redirectUri,
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,   // confidential
            'code_verifier' => $codeVerifier,
        ]);

        return OidcTokenResponse::fromArray($body);
    }

    /* ================================================================== */
    /* Token: refresh (Rule 1)                                              */
    /* ================================================================== */

    /**
     * Refresh the access token.
     *
     * Rule 1 — Confidential: ALWAYS send client_id + client_secret.
     *
     * @throws OidcException  TYPE_FATAL on invalid_client / invalid_grant
     *                        TYPE_RATE_LIMITED on 429
     *                        TYPE_TRANSIENT on network / 5xx
     */
    public function refreshAccessToken(string $refreshToken): OidcTokenResponse
    {
        $body = $this->callTokenEndpoint([
            'grant_type'    => 'refresh_token',
            'refresh_token' => $refreshToken,
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,   // confidential — must not be omitted
        ]);

        return OidcTokenResponse::fromArray($body);
    }

    /* ================================================================== */
    /* UserInfo (Rule 3)                                                    */
    /* ================================================================== */

    /**
     * Fetch user info from the discovered userinfo_endpoint.
     *
     * On 401: uses parseUserInfoError() — NestJS body shape (NOT oauth shape).
     *
     * @throws OidcException
     */
    public function getUserInfo(string $accessToken): array
    {
        $document = $this->discovery();
        $url      = $document['userinfo_endpoint']
            ?? throw new OidcException(
                'OIDC discovery: missing userinfo_endpoint',
                OidcException::TYPE_TRANSIENT,
            );

        $response = Http::withToken($accessToken)->get($url);

        if ($response->successful()) {
            return $response->json();
        }

        if ($response->status() === 401) {
            $body = $response->json() ?? [];
            throw $this->parseUserInfoError($body, 401);
        }

        throw new OidcException(
            "UserInfo request failed with HTTP {$response->status()}",
            OidcException::TYPE_TRANSIENT,
        );
    }

    /* ================================================================== */
    /* Introspect (Rule 3 — critical actions)                               */
    /* ================================================================== */

    /**
     * Introspect an access token via the discovered introspection_endpoint.
     *
     * @throws OidcException
     */
    public function introspect(string $accessToken): array
    {
        $document = $this->discovery();
        $url      = $document['introspection_endpoint']
            ?? throw new OidcException(
                'OIDC discovery: missing introspection_endpoint',
                OidcException::TYPE_TRANSIENT,
            );

        $response = Http::asForm()->post($url, [
            'token'         => $accessToken,
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
        ]);

        if ($response->successful()) {
            return $response->json();
        }

        throw new OidcException(
            "Introspect failed with HTTP {$response->status()}",
            OidcException::TYPE_TRANSIENT,
        );
    }

    /* ================================================================== */
    /* Revocation (Rule 4)                                                  */
    /* ================================================================== */

    /**
     * Request token revocation from the discovered revocation_endpoint.
     *
     * Rule 4 — always returns 200 {} (RFC 7009); do NOT treat 200 as proof.
     * Exceptions are swallowed — caller must have already purged local state.
     */
    public function revokeToken(string $token, string $tokenTypeHint = 'access_token'): void
    {
        try {
            $document = $this->discovery();
            $url      = $document['revocation_endpoint'] ?? null;

            if (! $url) {
                Log::warning('OIDC: revocation_endpoint not in discovery — skipping revoke');
                return;
            }

            Http::asForm()->post($url, [
                'token'           => $token,
                'token_type_hint' => $tokenTypeHint,
                'client_id'       => $this->clientId,
                'client_secret'   => $this->clientSecret,
            ]);
            // Rule 4: do NOT check the response. 200 is not proof of revocation.
        } catch (\Throwable $e) {
            Log::warning('OIDC: revokeToken HTTP error (local state already purged)', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /* ================================================================== */
    /* Internal: token endpoint caller with rate-limit gate (Rule 5)        */
    /* ================================================================== */

    /**
     * POST to the discovered token_endpoint.
     *
     * 1. Checks the global rate-limit gate before sending.
     * 2. On 429 → sets the gate, then throws TYPE_RATE_LIMITED.
     * 3. On fatal OAuth error → throws TYPE_FATAL.
     * 4. On 5xx / network error → throws TYPE_TRANSIENT.
     *
     * @throws OidcException
     */
    private function callTokenEndpoint(array $params): array
    {
        $this->checkRateLimitGate();      // Rule 5: honour existing gate

        $document = $this->discovery();
        $url      = $document['token_endpoint']
            ?? throw new OidcException(
                'OIDC discovery: missing token_endpoint',
                OidcException::TYPE_TRANSIENT,
            );

        try {
            $response = Http::asForm()
                ->timeout(15)
                ->post($url, $params);
        } catch (\Throwable $e) {
            throw new OidcException(
                'OIDC token endpoint connection error: ' . $e->getMessage(),
                OidcException::TYPE_TRANSIENT,
                previous: $e,
            );
        }

        $status = $response->status();
        $body   = $response->json() ?? [];

        if ($response->successful()) {
            return $body;
        }

        // ── 429 Rate limit (Rule 5 — NOT fatal) ─────────────────────────
        if ($status === 429) {
            $retryAfter = $this->parseRetryAfter($response->header('Retry-After'));
            $this->setRateLimitGate($retryAfter);

            throw new OidcException(
                'OIDC token endpoint rate-limited (429)',
                OidcException::TYPE_RATE_LIMITED,
                oauthError:       $body['error']             ?? 'ThrottlerException',
                errorDescription: $body['error_description'] ?? 'Too Many Requests',
                retryAfterSecs:   $retryAfter,
            );
        }

        // ── OAuth-style error body parser (for /token ONLY) ─────────────
        throw $this->parseTokenEndpointError($body, $status);
    }

    /* ================================================================== */
    /* Error parser #1 — token endpoint (OAuth style)                       */
    /* DO NOT use this parser for /userinfo responses.                      */
    /* ================================================================== */

    /**
     * Parse an error response from the token endpoint.
     *
     * Body shape: {"error":"invalid_grant","error_description":"..."}
     *
     * Fatal errors (Rule 2):
     *   - 401 invalid_client              → missing/wrong client_secret
     *   - any invalid_grant (400 or 401)  → token revoked/expired/user disabled
     */
    private function parseTokenEndpointError(array $body, int $status): OidcException
    {
        $error       = $body['error']             ?? '';
        $description = $body['error_description'] ?? '';

        // ── FATAL: invalid_client (401) ──────────────────────────────────
        if ($status === 401 && $error === 'invalid_client') {
            return new OidcException(
                "OIDC FATAL: invalid_client — missing or wrong client_secret",
                OidcException::TYPE_FATAL,
                oauthError:       $error,
                errorDescription: $description,
            );
        }

        // ── FATAL: invalid_grant (400 or 401, any description) ───────────
        if ($error === 'invalid_grant') {
            return new OidcException(
                "OIDC FATAL: invalid_grant ({$description})",
                OidcException::TYPE_FATAL,
                oauthError:       $error,
                errorDescription: $description,
            );
        }

        // ── Transient: 5xx or unrecognised error ─────────────────────────
        return new OidcException(
            "OIDC token endpoint error {$status}: {$error} — {$description}",
            OidcException::TYPE_TRANSIENT,
            oauthError:       $error,
            errorDescription: $description,
        );
    }

    /* ================================================================== */
    /* Error parser #2 — userinfo endpoint (NestJS style)                   */
    /* DO NOT use this parser for /token responses.                         */
    /* ================================================================== */

    /**
     * Parse an error response from the userinfo endpoint.
     *
     * Body shape: {"statusCode":401,"message":"invalid_token: revoked","error":"Unauthorized"}
     *
     * Error is in `message` (not `error`). Any "invalid_token…" → TYPE_FATAL
     * so the caller triggers the single-flight refresh cycle (Rule 3).
     */
    private function parseUserInfoError(array $body, int $status): OidcException
    {
        $message = $body['message'] ?? '';

        if (str_starts_with($message, 'invalid_token')) {
            return new OidcException(
                "OIDC userinfo: token not usable — {$message}",
                OidcException::TYPE_FATAL,   // triggers Rule 3 refresh cycle
                oauthError:       null,       // NestJS shape has no oauth error code
                errorDescription: $message,
            );
        }

        return new OidcException(
            "OIDC userinfo error {$status}: {$message}",
            OidcException::TYPE_TRANSIENT,
        );
    }

    /* ================================================================== */
    /* Rate-limit gate helpers (Rule 5)                                     */
    /* ================================================================== */

    /** @throws OidcException TYPE_RATE_LIMITED if the gate is currently closed. */
    private function checkRateLimitGate(): void
    {
        $gateKey  = config('oidc.rate_limit_cache_key', 'oidc:rate_limit_gate');
        $resumeAt = Cache::get($gateKey);

        if ($resumeAt && time() < $resumeAt) {
            $waitSecs = $resumeAt - time();
            throw new OidcException(
                "OIDC token endpoint rate-limit gate is closed for {$waitSecs}s",
                OidcException::TYPE_RATE_LIMITED,
                retryAfterSecs: $waitSecs,
            );
        }
    }

    /** Set the global rate-limit gate in the shared cache. */
    private function setRateLimitGate(int $retryAfterSecs): void
    {
        $gateKey  = config('oidc.rate_limit_cache_key', 'oidc:rate_limit_gate');
        $resumeAt = time() + max(1, $retryAfterSecs);
        $jitter   = rand(0, 5);

        Cache::put($gateKey, $resumeAt + $jitter, $retryAfterSecs + 60);

        Log::warning('OIDC: rate-limit gate set', [
            'resume_at'   => date('c', $resumeAt),
            'retry_after' => $retryAfterSecs,
        ]);
    }

    /**
     * Parse the Retry-After header value.
     * Accepts delta-seconds ("30") or an HTTP-date ("Mon, 26 Oct 2026 12:00:30 GMT").
     */
    private function parseRetryAfter(?string $header): int
    {
        if (empty($header)) {
            return 60;
        }

        if (is_numeric($header)) {
            return max(1, (int) $header);
        }

        $ts = strtotime($header);
        if ($ts !== false && $ts > time()) {
            return $ts - time();
        }

        return 60;
    }
}
