<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * (เฉพาะ APP_DEBUG=true) ใส่ header Server-Timing บอกจำนวน/เวลา query ฐานข้อมูลของแต่ละหน้า
 * ใช้ดูว่าหน้าไหนช้าเพราะฐานข้อมูลหรือเพราะ PHP/ตัวหน้าเอง - ปิดเองเมื่อ APP_DEBUG=false
 */
class ServerTiming
{
    public function handle(Request $request, Closure $next)
    {
        if (! config('app.debug')) {
            return $next($request);
        }

        $GLOBALS['__qshape'] = [];
        $count = 0;
        $ms = 0.0;
        $slow = [];
        DB::listen(function ($q) use (&$count, &$ms, &$slow) {
            $count++;
            $ms += $q->time;
            $slow[] = [$q->time, substr(preg_replace('/\s+/', ' ', $q->sql), 0, 90)];
            $shape = substr(preg_replace(['/\s+/', '/\(([?, ]+)\)/', '/in \([^)]*\)/'], [' ', '(?)', 'in (..)'], $q->sql), 0, 110);
            $GLOBALS['__qshape'][$shape] = ($GLOBALS['__qshape'][$shape] ?? 0) + 1;
        });
        $start = microtime(true);

        $response = $next($request);

        usort($slow, fn ($a, $b) => $b[0] <=> $a[0]);
        $top = implode(' | ', array_map(fn ($r) => round($r[0], 1) . 'ms ' . $r[1], array_slice($slow, 0, 3)));
        $response->headers->set('Server-Timing', sprintf('app;dur=%.0f, db;dur=%.0f;desc="%d queries"', (microtime(true) - $start) * 1000, $ms, $count));
        $shapes = $GLOBALS['__qshape'] ?? []; arsort($shapes);
        $response->headers->set('X-Query-Shapes', preg_replace('/[^\x20-\x7E]/', '?', implode(' || ', array_map(fn ($k, $v) => $v . 'x ' . $k, array_keys(array_slice($shapes, 0, 6, true)), array_slice($shapes, 0, 6, true)))));
        $response->headers->set('X-Slow-Queries', preg_replace('/[^\x20-\x7E]/', '?', $top));

        return $response;
    }
}
