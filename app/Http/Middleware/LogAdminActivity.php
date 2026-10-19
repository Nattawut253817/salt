<?php

namespace App\Http\Middleware;

use App\Support\ActivityLog;
use Closure;
use Illuminate\Http\Request;

/**
 * บันทึกการแก้ไขข้อมูลสำคัญของผู้ดูแล (ใช้กับกลุ่ม route /admin)
 *
 * บันทึกเฉพาะคำขอที่ "เปลี่ยนแปลงข้อมูล" (POST/PUT/PATCH/DELETE) ของ route ที่อยู่ในรายการด้านล่าง
 * และทำสำเร็จเท่านั้น (ไม่บันทึกกรณี validation ไม่ผ่านหรือมีข้อความ error)
 * ไม่เก็บเนื้อหาที่กรอก เก็บเฉพาะชื่อการกระทำและรหัสของรายการที่ถูกแก้
 */
class LogAdminActivity
{
    /** ชื่อ route => [ประเภท log, คำอธิบาย] */
    private const ACTIONS = [
        // แบบประเมินลดการบริโภคเกลือ
        'admin.salt-assessment.confirm' => ['data_change', 'สคร. ยืนยัน/ยกเลิกยืนยัน แบบประเมินลดการบริโภคเกลือ'],
        'admin.salt-assessment.destroy' => ['data_change', 'ลบแบบประเมินลดการบริโภคเกลือ'],
        'admin.salt-assessment.delete-filtered' => ['data_change', 'SuperAdmin ลบข้อมูลแบบประเมินลดการบริโภคเกลือตามตัวกรอง'],
        'admin.report-progress.store' => ['data_change', 'บันทึกรายงานผลลดการบริโภคเกลือ'],
        'admin.report-progress.remove-file' => ['data_change', 'ลบไฟล์แนบรายงานลดการบริโภคเกลือ'],
        // พชอ.ไต
        'admin.kidney-dhb.store' => ['data_change', 'บันทึกรายงาน พชอ.ไต'],
        'admin.kidney-dhb.update' => ['data_change', 'แก้ไขรายงาน พชอ.ไต'],
        'admin.kidney-dhb.confirm' => ['data_change', 'สคร. ยืนยัน/ยกเลิกยืนยัน รายงาน พชอ.ไต'],
        'admin.kidney-dhb.destroy' => ['data_change', 'ลบรายงาน พชอ.ไต'],
        'admin.kidney-dhb.delete-filtered' => ['data_change', 'SuperAdmin ลบข้อมูลรายงาน พชอ.ไต ตามตัวกรอง'],
        'admin.kidney-dhb.areas.store' => ['data_change', 'ปักพื้นที่ดำเนินงาน พชอ.ไต ใหม่'],
        'admin.kidney-dhb.areas.select' => ['data_change', 'เลือกพื้นที่ดำเนินงาน พชอ.ไต ให้หน่วยงาน'],
        'admin.kidney-dhb.areas.deselect' => ['data_change', 'ยกเลิกการเลือกพื้นที่ดำเนินงาน พชอ.ไต'],
        'admin.operating-areas.store' => ['data_change', 'เพิ่มพื้นที่ดำเนินงาน'],
        'admin.operating-areas.destroy' => ['data_change', 'ลบพื้นที่ดำเนินงาน'],
        'admin.operating-areas.delete-filtered' => ['data_change', 'SuperAdmin ลบพื้นที่ดำเนินงานตามตัวกรอง'],
        'admin.operating-areas.update' => ['data_change', 'SuperAdmin แก้ไขพื้นที่ดำเนินงาน'],
        'admin.operating-areas.release' => ['data_change', 'SuperAdmin ยกเลิกการเลือกพื้นที่ดำเนินงาน (คืนพื้นที่ให้ว่าง)'],
        // เมนู / ผลิตภัณฑ์ลดโซเดียม
        'admin.sodium-products.store' => ['data_change', 'เพิ่มผลิตภัณฑ์ลดโซเดียม'],
        'admin.sodium-products.import' => ['data_change', 'นำเข้าข้อมูลผลิตภัณฑ์ลดโซเดียม'],
        'admin.sodium-products.update' => ['data_change', 'แก้ไขผลิตภัณฑ์ลดโซเดียม'],
        'admin.sodium-products.destroy' => ['data_change', 'ลบผลิตภัณฑ์ลดโซเดียม'],
        'admin.sodium-products.bulk-delete' => ['data_change', 'ลบผลิตภัณฑ์ลดโซเดียมหลายรายการ'],
        'admin.sodium-products.bulk-delete-selected' => ['data_change', 'ลบผลิตภัณฑ์ลดโซเดียมที่เลือก'],
        'admin.sodium-menus.store' => ['data_change', 'เพิ่มเมนูลดโซเดียม'],
        'admin.sodium-menus.import' => ['data_change', 'นำเข้าข้อมูลเมนูลดโซเดียม'],
        'admin.sodium-menus.update' => ['data_change', 'แก้ไขเมนูลดโซเดียม'],
        'admin.sodium-menus.destroy' => ['data_change', 'ลบเมนูลดโซเดียม'],
        'admin.sodium-menus.bulk-delete' => ['data_change', 'ลบเมนูลดโซเดียมหลายรายการ'],
        'admin.sodium-menus.bulk-delete-selected' => ['data_change', 'ลบเมนูลดโซเดียมที่เลือก'],
        // ความตระหนักรู้ / HT
        'admin.awareness.import' => ['data_change', 'นำเข้าข้อมูลแบบประเมินความตระหนักรู้'],
        'admin.awareness.delete-filtered' => ['data_change', 'ลบข้อมูลแบบประเมินความตระหนักรู้ตามตัวกรอง'],
        'admin.hi.import' => ['data_change', 'นำเข้าข้อมูลอัตราป่วยรายใหม่ HT'],
        'admin.hi.delete-filtered' => ['data_change', 'ลบข้อมูลอัตราป่วยรายใหม่ HT ตามตัวกรอง'],
        // ผู้ใช้งาน / ปีงบประมาณ
        'admin.users.toggle-approval' => ['data_change', 'อนุมัติ/ยกเลิกอนุมัติผู้ใช้งาน'],
        'admin.users.toggle-super-admin' => ['setting_change', 'มอบ/ถอนสิทธิ์ SuperAdmin'],
        'admin.users.update' => ['data_change', 'แก้ไขข้อมูลผู้ใช้งาน'],
        'admin.users.destroy' => ['data_change', 'ลบผู้ใช้งาน'],
        'admin.fiscal-years.store' => ['data_change', 'เพิ่มปีงบประมาณ'],
        'admin.fiscal-years.destroy' => ['data_change', 'ลบปีงบประมาณ'],
        // ตั้งค่าระบบ
        'admin.system-management.toggle-otp' => ['setting_change', 'เปิด/ปิดการยืนยัน OTP เมื่อเข้าสู่ระบบ'],
        'admin.system-management.toggle-rank1-register' => ['setting_change', 'เปิด/ปิดรับสมัครสมาชิกระดับ สคร.'],
        'admin.system-management.reset-page-views' => ['setting_change', 'รีเซ็ตจำนวนการเข้าชมเว็บไซต์'],
    ];

    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        try {
            if (! in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
                return $response;
            }

            // มีการเปลี่ยนแปลงข้อมูลโดยผู้ดูแล -> ล้างแคชหน้าสาธารณะ เพื่อให้หน้าแรก/รายงานแสดงข้อมูลล่าสุดทันที
            PublicPageCache::bump();

            $route = $request->route();
            $name = $route ? (string) $route->getName() : '';
            if (! isset(self::ACTIONS[$name]) || $response->getStatusCode() >= 400) {
                return $response;
            }

            // ทำไม่สำเร็จ (validation ไม่ผ่าน หรือมีข้อความ error) -> ไม่บันทึก
            $session = $request->hasSession() ? $request->session() : null;
            if ($session) {
                $flashedNow = (array) $session->get('_flash.new', []);
                if (in_array('errors', $flashedNow, true) || in_array('error', $flashedNow, true)) {
                    return $response;
                }
            }

            [$event, $description] = self::ACTIONS[$name];

            $meta = ['route' => $name];
            $params = $route ? $route->parameters() : [];
            if (isset($params['id'])) {
                $meta['id'] = (string) $params['id'];
            }
            if (in_array($name, ['admin.system-management.toggle-otp', 'admin.system-management.toggle-rank1-register'], true)) {
                $meta['enabled'] = $request->boolean('enabled');
                $description .= $meta['enabled'] ? ' → เปิด' : ' → ปิด';
            }

            ActivityLog::record($event, $description, null, $meta, $request);
        } catch (\Throwable $e) {
            report($e);
        }

        return $response;
    }
}
