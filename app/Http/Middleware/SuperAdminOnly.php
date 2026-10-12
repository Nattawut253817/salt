<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * จำกัดเฉพาะ SuperAdmin (ดู User::isSuperAdmin()) - ผู้ใช้อื่นรวมถึง สคร. ระดับ 1 ทั่วไป
 * จะได้หน้า 404 เหมือนไม่มีเมนูนี้อยู่ในระบบ
 */
class SuperAdminOnly
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        abort_unless($user && $user->isSuperAdmin(), 404);

        return $next($request);
    }
}
