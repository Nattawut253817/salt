<?php

namespace Tests\Unit;

use App\Services\OidcClient;
use App\Services\OidcException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * ODPCX IdP — OidcClient Unit Tests
 *
 * Covers the full checklist from the ODPCX IdP Security Hardening brief.
 * All HTTP calls are mocked via Laravel's Http::fake().
 * Cache is mocked in-memory via the Laravel test helpers.
 */
class OidcClientTest extends TestCase
{
    private OidcClient $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = new OidcClient(
            idpBase:      'https://api.idp.akaratmakebugs.store',
            clientId:     'test-client-id',
            clientSecret: 'test-client-secret',
            redirectUri:  'https://example.com/oauth/callback',
        );
    }

    /* ================================================================== */
    /* Rule 1 — Refresh sends client_id + client_secret (confidential)     */
    /* ================================================================== */

    /** Confidential refresh WITH secret → 200, new tokens returned. */
    public function test_confidential_refresh_with_secret_returns_new_tokens(): void
    {
        Http::fake([
            '*/v1/oauth/token' => Http::response([
                'access_token'  => 'new-access-token',
                'refresh_token' => 'new-refresh-token',
                'expires_in'    => 900,
                'token_type'    => 'Bearer',
            ], 200),
        ]);

        $result = $this->client->refreshAccessToken('old-refresh-token');

        $this->assertSame('new-access-token',  $result->accessToken);
        $this->assertSame('new-refresh-token', $result->refreshToken);
        $this->assertSame(900, $result->expiresIn);

        Http::assertSent(function ($request) {
            $body = $request->data();
            // Must include client_id AND client_secret (confidential rule)
            return $body['client_id']     === 'test-client-id'
                && $body['client_secret'] === 'test-client-secret'
                && $body['grant_type']    === 'refresh_token'
                && $body['refresh_token'] === 'old-refresh-token';
        });
    }

    /* ================================================================== */
    /* Rule 2 — Fatal: invalid_client → purge + re-auth, no retry          */
    /* ================================================================== */

    /** Confidential refresh WITHOUT secret (wrong/missing) → 401 invalid_client → FATAL. */
    public function test_invalid_client_401_throws_fatal_exception(): void
    {
        Http::fake([
            '*/v1/oauth/token' => Http::response([
                'error'             => 'invalid_client',
                'error_description' => 'Client authentication failed',
            ], 401),
        ]);

        $this->expectException(OidcException::class);
        $this->expectExceptionMessage('OIDC FATAL: invalid_client');

        try {
            $this->client->refreshAccessToken('some-refresh-token');
        } catch (OidcException $e) {
            $this->assertTrue($e->isFatal());
            $this->assertSame('invalid_client', $e->getOauthError());
            // No retry should have happened — only one HTTP call
            Http::assertSentCount(1);
            throw $e;
        }
    }

    /* ================================================================== */
    /* Rule 2 — Fatal: invalid_grant (HTTP 400) → FATAL                    */
    /* ================================================================== */

    /** invalid_grant with HTTP 400 (invalid refresh_token) → FATAL. */
    public function test_invalid_grant_400_invalid_refresh_token_is_fatal(): void
    {
        Http::fake([
            '*/v1/oauth/token' => Http::response([
                'error'             => 'invalid_grant',
                'error_description' => 'invalid refresh_token',
            ], 400),
        ]);

        $this->expectException(OidcException::class);

        try {
            $this->client->refreshAccessToken('expired-refresh-token');
        } catch (OidcException $e) {
            $this->assertTrue($e->isFatal());
            $this->assertSame('invalid_grant', $e->getOauthError());
            $this->assertStringContainsString('invalid refresh_token', $e->getErrorDescription() ?? '');
            throw $e;
        }
    }

    /** invalid_grant with HTTP 401 (user disabled) → FATAL. */
    public function test_invalid_grant_401_user_disabled_is_fatal(): void
    {
        Http::fake([
            '*/v1/oauth/token' => Http::response([
                'error'             => 'invalid_grant',
                'error_description' => 'user disabled',
            ], 401),
        ]);

        $this->expectException(OidcException::class);

        try {
            $this->client->refreshAccessToken('any-token');
        } catch (OidcException $e) {
            $this->assertTrue($e->isFatal(), 'user disabled must be FATAL');
            $this->assertSame('invalid_grant', $e->getOauthError());
            throw $e;
        }
    }

    /** invalid_grant access restricted (role_denied) → FATAL. */
    public function test_invalid_grant_access_restricted_role_denied_is_fatal(): void
    {
        Http::fake([
            '*/v1/oauth/token' => Http::response([
                'error'             => 'invalid_grant',
                'error_description' => 'access restricted (role_denied)',
            ], 400),
        ]);

        $this->expectException(OidcException::class);

        try {
            $this->client->refreshAccessToken('any-token');
        } catch (OidcException $e) {
            $this->assertTrue($e->isFatal());
            $this->assertStringStartsWith('access restricted', $e->getErrorDescription() ?? '');
            throw $e;
        }
    }

    /* ================================================================== */
    /* Rule 3 — UserInfo error parser (NestJS shape, separate from /token) */
    /* ================================================================== */

    /** /userinfo → 401 invalid_token: revoked → throws FATAL via parser #2. */
    public function test_userinfo_invalid_token_revoked_throws_fatal(): void
    {
        Http::fake([
            '*/v1/oauth/userinfo' => Http::response([
                'statusCode' => 401,
                'message'    => 'invalid_token: revoked',
                'error'      => 'Unauthorized',
            ], 401),
        ]);

        $this->expectException(OidcException::class);

        try {
            $this->client->getUserInfo('stale-access-token');
        } catch (OidcException $e) {
            $this->assertTrue($e->isFatal(), 'invalid_token must be FATAL (triggers Rule 3 refresh)');
            // NestJS error shape — no oauthError field
            $this->assertNull($e->getOauthError(), 'NestJS shape has no oauth error code');
            $this->assertStringStartsWith('invalid_token', $e->getErrorDescription() ?? '');
            throw $e;
        }
    }

    /** /userinfo → 401 invalid_token: user not found → throws FATAL via parser #2. */
    public function test_userinfo_invalid_token_user_not_found_throws_fatal(): void
    {
        Http::fake([
            '*/v1/oauth/userinfo' => Http::response([
                'statusCode' => 401,
                'message'    => 'invalid_token: user not found',
                'error'      => 'Unauthorized',
            ], 401),
        ]);

        try {
            $this->client->getUserInfo('token');
        } catch (OidcException $e) {
            $this->assertTrue($e->isFatal());
            $this->assertStringStartsWith('invalid_token', $e->getErrorDescription() ?? '');
            return;
        }

        $this->fail('Expected OidcException was not thrown');
    }

    /** /userinfo → 401 invalid_token (bare) → FATAL. */
    public function test_userinfo_bare_invalid_token_is_fatal(): void
    {
        Http::fake([
            '*/v1/oauth/userinfo' => Http::response([
                'statusCode' => 401,
                'message'    => 'invalid_token',
                'error'      => 'Unauthorized',
            ], 401),
        ]);

        try {
            $this->client->getUserInfo('token');
        } catch (OidcException $e) {
            $this->assertTrue($e->isFatal());
            return;
        }
        $this->fail('Expected OidcException was not thrown');
    }

    /** /userinfo → success after fresh token → returns claims array. */
    public function test_userinfo_success_returns_claims(): void
    {
        $claims = ['sub' => 'user-123', 'email' => 'test@example.com', 'nonce' => 'abc'];

        Http::fake([
            '*/v1/oauth/userinfo' => Http::response($claims, 200),
        ]);

        $result = $this->client->getUserInfo('valid-access-token');

        $this->assertSame($claims, $result);
    }

    /* ================================================================== */
    /* Two parsers are SEPARATE — token parser does not read 'message'     */
    /* ================================================================== */

    /** /token error body has 'error' field; /userinfo has 'message' field.
     *  Verify token parser reads 'error', not 'message'. */
    public function test_token_endpoint_parser_reads_error_field_not_message(): void
    {
        // Simulate a /token response that uses 'error' key (OAuth style)
        Http::fake([
            '*/v1/oauth/token' => Http::response([
                'error'             => 'invalid_grant',
                'error_description' => 'invalid refresh_token',
                // NOTE: no 'message' or 'statusCode' field — that's userinfo shape
            ], 400),
        ]);

        try {
            $this->client->refreshAccessToken('any');
        } catch (OidcException $e) {
            $this->assertSame('invalid_grant', $e->getOauthError());
            $this->assertTrue($e->isFatal());
            return;
        }
        $this->fail('Expected OidcException');
    }

    /* ================================================================== */
    /* Rule 5 — 429 rate-limit: NOT fatal, sets gate, retryAfter parsed    */
    /* ================================================================== */

    /** 429 with Retry-After: 30 → exception TYPE_RATE_LIMITED, no purge. */
    public function test_429_throws_rate_limited_not_fatal(): void
    {
        Http::fake([
            '*/v1/oauth/token' => Http::response([
                'error'             => 'ThrottlerException',
                'error_description' => 'Too Many Requests',
            ], 429, ['Retry-After' => '30']),
        ]);

        try {
            $this->client->refreshAccessToken('any-token');
        } catch (OidcException $e) {
            $this->assertFalse($e->isFatal(),        '429 must NOT be fatal');
            $this->assertTrue($e->isRateLimited(),   '429 must be rate-limited');
            $this->assertSame(30, $e->getRetryAfterSecs());
            return;
        }

        $this->fail('Expected OidcException was not thrown');
    }

    /** Rate-limit gate in cache blocks subsequent requests. */
    public function test_rate_limit_gate_blocks_subsequent_calls(): void
    {
        // Set the gate manually (simulate previous 429)
        $gateKey = config('oidc.rate_limit_cache_key', 'oidc:rate_limit_gate');
        Cache::put($gateKey, time() + 60, 120);

        // No HTTP call should be made — gate should block it
        Http::fake();

        try {
            $this->client->refreshAccessToken('any-token');
        } catch (OidcException $e) {
            $this->assertTrue($e->isRateLimited());
            Http::assertNothingSent();   // gate prevented any HTTP request
            return;
        }

        $this->fail('Expected OidcException from rate-limit gate');
    }

    /* ================================================================== */
    /* Rule 4 — Revocation always returns 200; local state purge is caller's */
    /* ================================================================== */

    /** revokeToken() calls IdP and does NOT throw even if IdP returns 200. */
    public function test_revoke_token_does_not_throw(): void
    {
        Http::fake([
            '*/v1/oauth/token/revoke' => Http::response([], 200),
        ]);

        // Must not throw — 200 {} is the contract per RFC 7009
        $this->client->revokeToken('some-access-token', 'access_token');

        Http::assertSent(function ($request) {
            return str_ends_with($request->url(), '/v1/oauth/token/revoke')
                && $request->data()['token'] === 'some-access-token'
                && $request->data()['client_id'] === 'test-client-id';
        });

        // This test passes because no exception is thrown.
        $this->assertTrue(true);
    }

    /** revokeToken() swallows network errors (local purge already done by caller). */
    public function test_revoke_token_swallows_network_errors(): void
    {
        Http::fake([
            '*/v1/oauth/token/revoke' => fn() => throw new \Exception('connection refused'),
        ]);

        // Must not throw — caller already purged local state
        $this->client->revokeToken('some-token');

        $this->assertTrue(true);
    }

    /* ================================================================== */
    /* PKCE generation                                                       */
    /* ================================================================== */

    public function test_pkce_generates_valid_s256_pair(): void
    {
        $pkce = $this->client->generatePkce();

        $this->assertArrayHasKey('verifier',  $pkce);
        $this->assertArrayHasKey('challenge', $pkce);
        $this->assertArrayHasKey('method',    $pkce);
        $this->assertSame('S256', $pkce['method']);

        // Verify the challenge: SHA-256 of verifier, base64url-encoded
        $expected = rtrim(strtr(base64_encode(hash('sha256', $pkce['verifier'], true)), '+/', '-_'), '=');
        $this->assertSame($expected, $pkce['challenge']);
    }

    /* ================================================================== */
    /* OidcTokenResponse — expiry helpers                                   */
    /* ================================================================== */

    public function test_token_response_is_not_expired_when_fresh(): void
    {
        $tr = new \App\Services\OidcTokenResponse(
            accessToken:  'at',
            refreshToken: 'rt',
            expiresIn:    900,
            expiresAt:    time() + 900,
        );

        $this->assertFalse($tr->isExpired());
    }

    public function test_token_response_is_expired_when_past(): void
    {
        $tr = new \App\Services\OidcTokenResponse(
            accessToken:  'at',
            refreshToken: 'rt',
            expiresIn:    900,
            expiresAt:    time() - 1,
        );

        $this->assertTrue($tr->isExpired());
    }

    public function test_token_from_array_sets_expires_at_correctly(): void
    {
        $now  = time();
        $data = ['access_token' => 'at', 'refresh_token' => 'rt', 'expires_in' => 900];
        $tr   = \App\Services\OidcTokenResponse::fromArray($data, $now);

        $this->assertSame($now + 900, $tr->expiresAt);
    }

    /* ================================================================== */
    /* Introspect (Rule 3)                                                  */
    /* ================================================================== */

    public function test_introspect_active_token_returns_active_true(): void
    {
        Http::fake([
            '*/v1/oauth/token/introspect' => Http::response([
                'active' => true,
                'sub'    => 'user-123',
                'exp'    => time() + 900,
            ], 200),
        ]);

        $result = $this->client->introspect('valid-access-token');

        $this->assertTrue($result['active']);
        $this->assertSame('user-123', $result['sub']);

        Http::assertSent(function ($request) {
            $body = $request->data();
            // Confidential: must send client_id + client_secret
            return $body['client_id']     === 'test-client-id'
                && $body['client_secret'] === 'test-client-secret'
                && $body['token']         === 'valid-access-token';
        });
    }

    public function test_introspect_inactive_token_returns_active_false(): void
    {
        Http::fake([
            '*/v1/oauth/token/introspect' => Http::response(['active' => false], 200),
        ]);

        $result = $this->client->introspect('revoked-token');
        $this->assertFalse($result['active']);
    }

    /* ================================================================== */
    /* Authorize URL                                                        */
    /* ================================================================== */

    public function test_build_authorize_url_includes_required_params(): void
    {
        $url = $this->client->buildAuthorizeUrl('state123', 'nonce456', 'challenge789');
        $parsed = parse_url($url);
        parse_str($parsed['query'] ?? '', $params);

        $this->assertSame('code',     $params['response_type']);
        $this->assertSame('test-client-id', $params['client_id']);
        $this->assertSame('https://example.com/oauth/callback', $params['redirect_uri']);
        $this->assertSame('state123', $params['state']);
        $this->assertSame('nonce456', $params['nonce']);
        $this->assertSame('challenge789', $params['code_challenge']);
        $this->assertSame('S256',      $params['code_challenge_method']);
        $this->assertStringContainsString('cid',  $params['scope']);
        $this->assertStringContainsString('mfa',  $params['scope']);
        $this->assertStringContainsString('openid', $params['scope']);
    }
}
