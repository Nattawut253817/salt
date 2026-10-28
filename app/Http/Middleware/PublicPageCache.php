<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * แคชผลลัพธ์ HTML ของหน้าสาธารณะ (หน้าหลัก/ความตระหนักรู้/เมนู/ผลิตภัณฑ์/HT/พชอ.ไต/แบบรายงานเกลือ)
 * ให้ผู้เยี่ยมชมทั่วไป เพื่อให้กดเปลี่ยนหน้าได้เร็ว - หน้าเหล่านี้คำนวณข้อมูลจำนวนมากทุกครั้งที่เปิด
 *
 * - แคชแยกตาม URL + ตัวกรอง (query string) และแยกคำขอ AJAX
 * - เฉพาะ GET ที่ตอบ 200 และผู้เยี่ยมชมที่ยังไม่ล็อกอิน (ผู้ดูแลเห็นข้อมูลสดเสมอ)
 * - ข้ามเมื่อมี flash/error ใน session (เช่นเพิ่งส่งฟอร์ม)
 * - หมดอายุเอง 60 วินาที และถูกล้างทันทีเมื่อผู้ดูแลบันทึก/ลบข้อมูล (ดู LogAdminActivity)
 * - สลับ CSRF token ในหน้าที่แคชให้เป็นของผู้เข้าชมคนปัจจุบันเสมอ
 */
class PublicPageCache
{
    private const TTL = 300;
    private const VERSION_KEY = 'public_page_cache_version';

    public static function bump(): void
    {
        Cache::forever(self::VERSION_KEY, microtime(true));
    }

    public function handle(Request $request, Closure $next)
    {
        if (! $this->cacheable($request)) {
            return $next($request);
        }

        $key = 'pubpage:' . Cache::get(self::VERSION_KEY, 0) . ':' . md5($request->fullUrl() . '|' . ($request->ajax() ? 'x' : 'p'));

        $hit = Cache::get($key);
        if (is_array($hit) && isset($hit['body'])) {
            $body = $hit['body'];
            if (! empty($hit['token']) && $request->hasSession()) {
                $body = str_replace($hit['token'], csrf_token(), $body);
            }
            return response($body, 200, ['Content-Type' => $hit['type'] ?? 'text/html; charset=UTF-8']);
        }

        $response = $next($request);

        try {
            if ($response->getStatusCode() === 200 && ! $response instanceof \Symfony\Component\HttpFoundation\StreamedResponse
                && ! $response instanceof \Symfony\Component\HttpFoundation\BinaryFileResponse && ! $this->hasFlash($request)) {
                Cache::put($key, [
                    'body' => $response->getContent(),
                    'token' => $request->hasSession() ? csrf_token() : null,
                    'type' => $response->headers->get('Content-Type'),
                ], self::TTL);
            }
        } catch (\Throwable $e) {
            report($e);
        }

        return $response;
    }

    private function cacheable(Request $request): bool
    {
        return $request->isMethod('GET')
            && ! auth()->check()
            && strlen((string) $request->getQueryString()) <= 300
            && ! $this->hasFlash($request);
    }

    private function hasFlash(Request $request): bool
    {
        if (! $request->hasSession()) {
            return false;
        }
        $s = $request->session();
        return $s->has('errors') || ! empty($s->get('_flash.old', [])) || ! empty($s->get('_flash.new', []));
    }
}
