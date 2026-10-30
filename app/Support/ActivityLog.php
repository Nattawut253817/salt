<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * บันทึกการใช้งานระบบ (audit log) ลงตาราง activity_logs
 *
 * - ไม่เก็บรหัสผ่าน / OTP / เนื้อหาที่ผู้ใช้กรอก เก็บเฉพาะ "ใคร ทำอะไร เมื่อไร จากที่ไหน"
 * - เก็บย้อนหลัง RETENTION_DAYS วัน แล้วลบอัตโนมัติ (ล้างเป็นครั้งคราวตอนมีการบันทึก ไม่ต้องตั้ง scheduler)
 * - สร้างตารางให้เองถ้ายังไม่ได้รัน migrate และไม่ทำให้หน้าเว็บล่มถ้าบันทึกไม่ได้
 */
class ActivityLog
{
    public const RETENTION_DAYS = 365;

    /** key => [ป้ายชื่อ, สี badge] */
    public const EVENTS = [
        'login_success' => ['เข้าสู่ระบบ', 'green'],
        'logout' => ['ออกจากระบบ', 'gray'],
        'login_failed' => ['เข้าสู่ระบบไม่สำเร็จ', 'red'],
        'otp_failed' => ['กรอก OTP ไม่ถูกต้อง', 'red'],
        'password_reset_requested' => ['ขอรหัส OTP ลืมรหัสผ่าน', 'amber'],
        'password_reset_otp_failed' => ['กรอก OTP ลืมรหัสผ่านไม่ถูกต้อง', 'red'],
        'password_reset_done' => ['ตั้งรหัสผ่านใหม่', 'amber'],
        'register' => ['สมัครสมาชิก', 'blue'],
        'data_change' => ['แก้ไขข้อมูลสำคัญ', 'indigo'],
        'setting_change' => ['เปลี่ยนการตั้งค่าระบบ', 'purple'],
    ];

    public static function record(string $event, ?string $description = null, $user = null, array $meta = [], ?Request $request = null, ?string $actorName = null): void
    {
        try {
            self::ensureTable();

            $request = $request ?: request();
            $user = $user ?: auth()->user();

            DB::table('activity_logs')->insert([
                'user_id' => $user ? $user->id : null,
                'actor_name' => mb_substr((string) ($actorName ?? ($user ? ($user->name ?: $user->email) : '')), 0, 255) ?: null,
                'event' => $event,
                'description' => $description !== null ? mb_substr($description, 0, 500) : null,
                'meta' => $meta ? json_encode($meta, JSON_UNESCAPED_UNICODE) : null,
                'ip' => $request ? mb_substr((string) $request->ip(), 0, 45) : null,
                'user_agent' => $request ? mb_substr((string) $request->userAgent(), 0, 255) : null,
                'created_at' => now(),
            ]);

            // ลบ log ที่เก่ากว่ากำหนดเป็นครั้งคราว (ประมาณ 1 ใน 100 ครั้งที่บันทึก)
            if (random_int(1, 100) === 1) {
                DB::table('activity_logs')->where('created_at', '<', now()->subDays(self::RETENTION_DAYS))->delete();
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /** In-process flag: set to true the first time this request confirms the table exists.
     *  Avoids a file-cache read on every subsequent ActivityLog::record() call in the same request. */
    private static bool $tableReady = false;

    public static function ensureTable(): void
    {
        if (self::$tableReady) {
            return;                         // ✓ already confirmed this request
        }
        if (Cache::get('activity_logs_table_ready')) {
            self::$tableReady = true;       // promote to in-process flag
            return;
        }

        if (! Schema::hasTable('activity_logs')) {
            Schema::create('activity_logs', function ($table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('actor_name', 255)->nullable();
                $table->string('event', 50)->index();
                $table->string('description', 500)->nullable();
                $table->text('meta')->nullable();
                $table->string('ip', 45)->nullable();
                $table->string('user_agent', 255)->nullable();
                $table->timestamp('created_at')->nullable()->index();
            });
        }

        self::$tableReady = true;
        Cache::forever('activity_logs_table_ready', true);
    }

    /** ย่อ User-Agent เป็น "เบราว์เซอร์ · ระบบปฏิบัติการ" */
    public static function device(?string $ua): string
    {
        $ua = (string) $ua;
        if ($ua === '') {
            return '-';
        }

        $browser = 'เบราว์เซอร์อื่น';
        foreach (['Edg' => 'Edge', 'OPR' => 'Opera', 'Firefox' => 'Firefox', 'Chrome' => 'Chrome', 'Safari' => 'Safari'] as $needle => $label) {
            if (stripos($ua, $needle) !== false) {
                $browser = $label;
                break;
            }
        }

        $os = 'ระบบอื่น';
        foreach (['Windows' => 'Windows', 'Android' => 'Android', 'iPhone' => 'iOS', 'iPad' => 'iOS', 'Mac OS' => 'macOS', 'Linux' => 'Linux'] as $needle => $label) {
            if (stripos($ua, $needle) !== false) {
                $os = $label;
                break;
            }
        }

        return $browser . ' · ' . $os;
    }
}
