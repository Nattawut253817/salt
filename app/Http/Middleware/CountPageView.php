<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;

/**
 * นับจำนวนการเข้าชมของหน้าสาธารณะ แยกเป็นรายหน้า (ใช้กับ route ที่ใส่ middleware 'page.view')
 *
 * - นับทุกครั้งที่โหลดหน้า (กดรีเฟรชก็นับเพิ่ม)
 * - ไม่นับ bot / crawler / คำขอ AJAX / คำขอที่ไม่ใช่ GET
 * - แชร์ตัวแปร $pageViewStat ให้ layout แสดงใต้แผนที่ท้ายเว็บ
 * - ถ้าตัวนับผิดพลาด (เช่นยังไม่มีตาราง) หน้าเว็บต้องทำงานต่อได้ตามปกติ
 */
class CountPageView
{
    private const BOT_PATTERN = '/bot|crawl|spider|slurp|preview|facebookexternalhit|curl|wget|monitor|uptime|headless|lighthouse/i';

    public function handle(Request $request, Closure $next)
    {
        try {
            if ($request->isMethod('GET') && ! $request->ajax() && ! $this->isBot($request)) {
                $this->track($request);
            }
        } catch (\Throwable $e) {
            report($e);
        }

        return $next($request);
    }

    private function track(Request $request): void
    {
        $key = (string) optional($request->route())->getName();
        if ($key === '') {
            return;
        }

        $this->ensureTable();

        // นับทุกครั้งที่โหลดหน้า (รวมการกดรีเฟรช) - ยกเว้นบอต/AJAX ซึ่งถูกกรองไว้ใน handle()
        // เคส "ปกติ" (มีแถวของหน้านี้อยู่แล้ว) ใช้แค่ UPDATE 1 คำสั่ง; ค่อย INSERT เฉพาะครั้งแรกของหน้านั้น
        // (เดิมสั่ง INSERT IGNORE ทุกครั้งที่โหลดหน้า ซึ่งเป็นการเขียนดิสก์เพิ่มโดยไม่จำเป็น)
        $touch = fn () => DB::table('page_views')->where('page_key', $key)->increment('views', 1, ['updated_at' => now()]);

        if ($touch() === 0) {
            $created = DB::table('page_views')->insertOrIgnore([
                'page_key' => $key,
                'views' => 1,
                'started_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            if ($created === 0) {
                // มีคำขออื่นสร้างแถวตัดหน้าไปพอดี - นับครั้งนี้ต่อจากแถวที่เพิ่งถูกสร้าง
                $touch();
            }
        }

        $row = DB::table('page_views')->where('page_key', $key)->first();
        if ($row) {
            View::share('pageViewStat', [
                'views' => (int) $row->views,
                'since' => \Illuminate\Support\Carbon::parse($row->started_at ?: $row->created_at),
            ]);
        }
    }

    /** สร้างตารางให้อัตโนมัติถ้ายังไม่ได้รัน migrate (จำผลไว้ใน cache เพื่อไม่ต้องเช็กซ้ำทุกครั้ง) */
    private function ensureTable(): void
    {
        if (Cache::get('page_views_table_ready')) {
            return;
        }

        if (! Schema::hasTable('page_views')) {
            Schema::create('page_views', function ($table) {
                $table->id();
                $table->string('page_key', 100)->unique();
                $table->unsignedBigInteger('views')->default(0);
                $table->timestamp('started_at')->nullable();
                $table->timestamps();
            });
        }

        Cache::forever('page_views_table_ready', true);
    }

    private function isBot(Request $request): bool
    {
        $ua = (string) $request->userAgent();

        return $ua === '' || preg_match(self::BOT_PATTERN, $ua) === 1
            || $request->headers->get('Purpose') === 'prefetch'
            || $request->headers->get('Sec-Purpose') === 'prefetch';
    }
}
