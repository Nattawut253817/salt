<?php

namespace App\Services;

/**
 * Exception thrown by OidcClient.
 *
 * errorType constants:
 *   TYPE_FATAL       — Rule 2: purge all tokens, cancel retries, redirect to /authorize
 *   TYPE_RATE_LIMITED — Rule 5: pause queue, jittered retry after Retry-After seconds
 *   TYPE_TRANSIENT   — network error / 5xx: bounded retry, no purge
 */
class OidcException extends \RuntimeException
{
    public const TYPE_FATAL        = 'fatal';
    public const TYPE_RATE_LIMITED = 'rate_limited';
    public const TYPE_TRANSIENT    = 'transient';

    public function __construct(
        string          $message,
        private string  $errorType,
        private ?string $oauthError       = null,
        private ?string $errorDescription = null,
        private ?int    $retryAfterSecs   = null,
        ?\Throwable     $previous         = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function getErrorType(): string   { return $this->errorType; }
    public function getOauthError(): ?string { return $this->oauthError; }
    public function getErrorDescription(): ?string { return $this->errorDescription; }
    public function getRetryAfterSecs(): ?int { return $this->retryAfterSecs; }

    public function isFatal(): bool        { return $this->errorType === self::TYPE_FATAL; }
    public function isRateLimited(): bool  { return $this->errorType === self::TYPE_RATE_LIMITED; }
    public function isTransient(): bool    { return $this->errorType === self::TYPE_TRANSIENT; }
}
