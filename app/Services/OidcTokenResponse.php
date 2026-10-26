<?php

namespace App\Services;

/**
 * Immutable value object returned by OidcClient token operations.
 */
readonly class OidcTokenResponse
{
    public function __construct(
        public string  $accessToken,
        public string  $refreshToken,
        public int     $expiresIn,       // seconds
        public int     $expiresAt,       // Unix timestamp
        public ?string $idToken    = null,
        public ?string $tokenType  = 'Bearer',
        public ?string $scope      = null,
    ) {}

    public static function fromArray(array $data, int $now = 0): self
    {
        $now       = $now ?: time();
        $expiresIn = (int) ($data['expires_in'] ?? 900);

        return new self(
            accessToken:  $data['access_token'],
            refreshToken: $data['refresh_token'],
            expiresIn:    $expiresIn,
            expiresAt:    $now + $expiresIn,
            idToken:      $data['id_token']    ?? null,
            tokenType:    $data['token_type']  ?? 'Bearer',
            scope:        $data['scope']       ?? null,
        );
    }

    /** Whether the access token has expired (with optional clock-skew buffer). */
    public function isExpired(int $bufferSecs = 30): bool
    {
        return time() >= ($this->expiresAt - $bufferSecs);
    }

    /** Whether proactive refresh should be attempted (80–90 % of lifetime consumed). */
    public function needsProactiveRefresh(int $minPct = 80, int $maxPct = 90): bool
    {
        $age        = time() - ($this->expiresAt - $this->expiresIn);
        $thresholdSecs = (int) ($this->expiresIn * rand($minPct, $maxPct) / 100);
        return $age >= $thresholdSecs;
    }
}
