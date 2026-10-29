<?php

namespace App\Auth;

use Illuminate\Auth\SessionGuard;

/**
 * Custom session guard that limits the "Remember Me" cookie lifetime
 * to 30 days instead of Laravel's default 5-year (forever) cookie.
 */
class RememberGuard extends SessionGuard
{
    /** Remember-me cookie lifetime in minutes (30 days). */
    protected int $rememberDuration = 43200; // 30 × 24 × 60

    /**
     * Create a remember-me cookie for the given recaller value
     * with a finite 30-day lifetime.
     */
    protected function createRecallerCookie($value)
    {
        return $this->getCookieJar()->make(
            $this->getRecallerName(),
            $value,
            $this->rememberDuration
        );
    }
}
