<?php

namespace Tests\Unit\Support;

use App\Support\SystemSettings;
use PHPUnit\Framework\TestCase;

/**
 * App\Support\SystemSettings is a tiny file-backed key/value store for
 * app-wide on/off switches - starting with "require OTP at login"
 * (see AuthController::login() and admin/system-management). A DB-table
 * settings store would need a migration; this needs none, which matters
 * here because migrations against this project's local MySQL can only be
 * run from the developer's own machine.
 *
 * Every test points the class at its own throwaway file via
 * setPathForTesting() so nothing here touches storage/app/system_settings.json.
 */
class SystemSettingsTest extends TestCase
{
    private string $testPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testPath = sys_get_temp_dir() . '/system_settings_test_' . uniqid() . '.json';
        SystemSettings::setPathForTesting($this->testPath);
    }

    protected function tearDown(): void
    {
        if (is_file($this->testPath)) {
            unlink($this->testPath);
        }
        SystemSettings::setPathForTesting(null);
        parent::tearDown();
    }

    public function test_missing_file_falls_back_to_default(): void
    {
        $this->assertSame(true, SystemSettings::get('login_otp_enabled', true));
        $this->assertSame([], SystemSettings::all());
    }

    public function test_set_then_get_round_trips_the_value(): void
    {
        SystemSettings::set('login_otp_enabled', false);

        $this->assertSame(false, SystemSettings::get('login_otp_enabled', true));
        $this->assertFileExists($this->testPath);
    }

    public function test_persisted_boolean_stays_a_real_bool_not_a_string(): void
    {
        SystemSettings::set('login_otp_enabled', false);

        $this->assertIsBool(SystemSettings::get('login_otp_enabled', true));
    }

    public function test_setting_again_updates_in_place(): void
    {
        SystemSettings::set('login_otp_enabled', false);
        SystemSettings::set('login_otp_enabled', true);

        $this->assertSame(true, SystemSettings::get('login_otp_enabled', false));
    }

    public function test_setting_one_key_preserves_other_existing_keys(): void
    {
        SystemSettings::set('login_otp_enabled', true);
        SystemSettings::set('another_flag', 'hello');

        $this->assertSame('hello', SystemSettings::get('another_flag', null));
        $this->assertSame(true, SystemSettings::get('login_otp_enabled', false));
    }

    public function test_absent_key_in_an_existing_file_falls_back_to_default(): void
    {
        SystemSettings::set('another_flag', 'hello');

        $this->assertSame('fallback', SystemSettings::get('nonexistent_key', 'fallback'));
    }

    public function test_corrupt_file_is_treated_as_empty_rather_than_throwing(): void
    {
        file_put_contents($this->testPath, 'not valid json {{{');

        $this->assertSame(true, SystemSettings::get('login_otp_enabled', true));
        $this->assertSame([], SystemSettings::all());
    }
}
