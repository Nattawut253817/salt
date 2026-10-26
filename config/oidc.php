<?php

return [

    /*
    |--------------------------------------------------------------------------
    | ODPCX IdP — OAuth 2.0 / OIDC client configuration
    | Enforcement deadline: 2026-11-01. No grace period.
    |--------------------------------------------------------------------------
    | This app is classified as a CONFIDENTIAL client:
    |   - Server-side Laravel, holds client_secret securely
    |   - client_secret MUST be sent on every /token call (body or Basic)
    */

    /*
    | OIDC Discovery document ({idp_base}/.well-known/openid-configuration).
    | All endpoint URLs (authorization, token, userinfo, introspection,
    | revocation) are resolved dynamically from this document — NO paths
    | are hardcoded. TTL: how long the discovery document is cached (s).
    */
    'idp_base'      => env('OIDC_IDP_BASE', 'https://api.idp.akaratmakebugs.store'),
    'discovery_ttl' => env('OIDC_DISCOVERY_TTL', 3600),
    'client_id'     => env('OIDC_CLIENT_ID', ''),
    'client_secret' => env('OIDC_CLIENT_SECRET', ''),    // confidential — never empty in production
    'redirect_uri'  => env('OIDC_REDIRECT_URI', ''),

    /*
    | Scopes required by the IdP policy.
    | DO NOT add or remove scopes without IdP approval.
    */
    'scopes' => ['openid', 'profile', 'email', 'cid', 'mfa'],

    /*
    | Access-token lifetime: 900 s (15 min).
    | Roles/claims MUST NOT be cached longer than this value.
    */
    'access_token_ttl' => 900,

    /*
    | Proactive-refresh window: refresh when access token is between
    | 80 % and 90 % of its lifetime (720 – 810 s used out of 900 s).
    | A random jitter within this window prevents refresh storms.
    */
    'proactive_refresh_min_pct' => 80,
    'proactive_refresh_max_pct' => 90,

    /*
    | Rate-limit gate (Rule 5):
    |   - Cache key that holds the "resume after" Unix timestamp.
    |   - All workers behind one NAT share this gate.
    */
    'rate_limit_cache_key' => 'oidc:rate_limit_gate',

    /*
    | Refresh single-flight lock TTL (seconds).
    | Prevents concurrent refresh storms per session.
    */
    'refresh_lock_ttl' => 30,

    /*
    | Maximum jittered backoff attempts after a 429.
    */
    'max_backoff_attempts' => 4,
    'base_backoff_ms'      => 500,
];
