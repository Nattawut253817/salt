<?php

namespace App\Support;

/**
 * Tiny file-backed key/value store for app-wide on/off switches (the first
 * one being "require OTP at login" - see AuthController::login()).
 *
 * This project's migrations can only be run from the user's own machine
 * (this sandbox has no PHP/DB access to it), so a DB-table-backed settings
 * store isn't practical to roll out from here. A single JSON file under
 * storage/app/ needs no migration and no artisan command - it just has to
 * exist (or not: a missing file simply means "every setting is at its
 * default") for the toggle to work.
 *
 * Not for secrets or anything written by end users - this is an admin-only
 * on/off switch store, read on every request that needs it.
 */
class SystemSettings
{
    /**
     * Overrides the real storage path for tests, so this class never has
     * to call storage_path() (unavailable outside a booted Laravel app)
     * to be exercised in isolation.
     */
    protected static ?string $pathOverride = null;

    public static function setPathForTesting(?string $path): void
    {
        static::$pathOverride = $path;
    }

    public static function path(): string
    {
        return static::$pathOverride ?? storage_path('app/system_settings.json');
    }

    /**
     * Every stored setting as an associative array. A missing file, or a
     * file that isn't valid JSON (hand-edited and broken, a concurrent
     * write caught mid-write, etc.), is treated as "nothing set yet"
     * rather than a fatal error - get() below then just returns whatever
     * default the caller asked for.
     */
    public static function all(): array
    {
        $path = static::path();
        if (!is_file($path)) {
            return [];
        }

        $raw = file_get_contents($path);
        if ($raw === false) {
            return [];
        }

        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }

    public static function get(string $key, $default = null)
    {
        $all = static::all();
        return array_key_exists($key, $all) ? $all[$key] : $default;
    }

    public static function set(string $key, $value): void
    {
        $all = static::all();
        $all[$key] = $value;

        $path = static::path();
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        file_put_contents($path, json_encode($all, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}
