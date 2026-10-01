<?php

namespace App\Http\Controllers;

/**
 * "จัดการแบบฟอร์ม Excel" (Manage Excel Forms) admin screen - reached from the
 * sidebar link right after "จัดการปีงบประมาณ" (rank 1 only, same
 * "จัดการระบบ" section).
 *
 * This is a read-only hub page: it does not create or store anything of its
 * own. It just gathers, in one place, the "ดาวน์โหลดแบบฟอร์ม Excel"
 * template-download links that already exist scattered across the admin
 * screens for each module, so an admin does not have to remember which page
 * each one lives on.
 *
 * As of this writing only 2 of the 6 modules (เมนูลดโซเดียม /
 * ผลิตภัณฑ์ลดโซเดียม) actually have a downloadable import template
 * (see AdminController::downloadSodiumMenusTemplate() /
 * downloadSodiumProductsTemplate()). The other 4 modules are listed here
 * too, marked "ยังไม่มีแบบฟอร์ม", so the page stays a complete map of every
 * module rather than silently omitting them - building new template
 * exports for those modules is a separate, larger task not covered here.
 */
class ExcelTemplateController extends Controller
{
    public function index()
    {
        $templates = [
            [
                'title' => 'เมนูลดโซเดียม',
                'subtitle' => 'แบบฟอร์มนำเข้าเมนูลดโซเดียม',
                'icon' => 'fa-utensils',
                'color' => '#16a34a',
                'route' => 'admin.sodium-menus.template',
            ],
            [
                'title' => 'ผลิตภัณฑ์ลดโซเดียม',
                'subtitle' => 'แบบฟอร์มนำเข้าผลิตภัณฑ์ลดโซเดียม',
                'icon' => 'fa-box-open',
                'color' => '#0ea5e9',
                'route' => 'admin.sodium-products.template',
            ],
            [
                'title' => 'การประเมินความตระหนักรู้',
                'subtitle' => null,
                'icon' => 'fa-lightbulb',
                'color' => '#7c3aed',
                'route' => null,
            ],
            [
                'title' => 'อัตราป่วยรายใหม่ HT',
                'subtitle' => null,
                'icon' => 'fa-heart-pulse',
                'color' => '#db2777',
                'route' => null,
            ],
            [
                'title' => 'รายการข้อมูล พชอ.ไต',
                'subtitle' => null,
                'icon' => 'fa-file-medical',
                'color' => '#f59e0b',
                'route' => null,
            ],
            [
                'title' => 'แบบประเมินลดการบริโภคเกลือ',
                'subtitle' => null,
                'icon' => 'fa-clipboard-check',
                'color' => '#6366f1',
                'route' => null,
            ],
        ];

        return view('admin.excel-templates.index', compact('templates'));
    }
}