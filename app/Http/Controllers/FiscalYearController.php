<?php

namespace App\Http\Controllers;

use App\Models\FiscalYear;
use App\Models\Hi;
use App\Models\KidneyAssessment;
use App\Models\ReducedSodiumMenu;
use App\Models\ReducedSodiumProduct;
use App\Models\SaltAssessment;
use App\Models\SodiumSurvey;
use Illuminate\Http\Request;

/**
 * "จัดการปีงบประมาณ" (Manage Fiscal Years) admin screen - reached from the
 * sidebar link right after "จัดการผู้ใช้งาน" (rank 1 only, same
 * "จัดการระบบ" section).
 *
 * Every module scoped by fiscal year gets its OWN list of years here, built
 * from three things:
 *  - "real" years: whatever distinct fiscal years already exist in that
 *    module's own data (same query each module's own controller already
 *    uses for its filter dropdown) - shown with a green dot, never
 *    deletable outright (there is nothing to delete - it's a fact about
 *    the data, not a setting). Can be HIDDEN instead (see below).
 *  - "enabled" years: fiscal_years rows with hidden=false, added via
 *    store() - these let an admin make a brand new fiscal year (e.g. next
 *    year) selectable before any data exists for it yet. Fully deletable.
 *  - "hidden" years: fiscal_years rows with hidden=true - an override that
 *    removes a real-data year from the dropdowns without touching the
 *    underlying data at all. Shown greyed out here, with a restore
 *    button (which just deletes the hidden row).
 *
 * See App\Models\FiscalYear::selectableYearsFor() - every OTHER controller
 * that needs a module's full selectable-years list (search filters, upload
 * modals) calls that one helper with its own real-years query, so this
 * screen never disagrees with what those dropdowns actually offer.
 */
class FiscalYearController extends Controller
{
    /**
     * Module keys + their real-years query, in this page's display order.
     * Kept in one place so index()/addYear() stay trivially in sync with
     * the actual list of manageable modules (App\Models\FiscalYear::MODULES).
     */
    protected function moduleDefinitions(): array
    {
        return [
            'awareness' => [
                'title' => 'การประเมินความตระหนักรู้',
                'subtitle' => 'รวมตั้งค่าเกณฑ์ความตระหนักรู้ และตั้งค่าแดชบอร์ด',
                'icon' => 'fa-lightbulb',
                'color' => '#7c3aed',
                'real_years' => fn () => SodiumSurvey::query()->distinct()->pluck('fiscal_year'),
            ],
            'sodium_menus' => [
                'title' => 'เมนูลดโซเดียม',
                'subtitle' => null,
                'icon' => 'fa-utensils',
                'color' => '#16a34a',
                'real_years' => fn () => ReducedSodiumMenu::whereNotNull('year')
                    ->where('year', '!=', '')
                    ->distinct()
                    ->pluck('year')
                    ->map(fn ($y) => trim($y)),
            ],
            'sodium_products' => [
                'title' => 'ผลิตภัณฑ์ลดโซเดียม',
                'subtitle' => null,
                'icon' => 'fa-box-open',
                'color' => '#0ea5e9',
                'real_years' => function () {
                    $fiscalYearValues = ReducedSodiumProduct::whereNotNull('fiscal_year')
                        ->where('fiscal_year', '!=', 0)
                        ->distinct()
                        ->pluck('fiscal_year');
                    $legacyYearValues = ReducedSodiumProduct::where(function ($q) {
                            $q->whereNull('fiscal_year')->orWhere('fiscal_year', 0);
                        })
                        ->selectRaw('YEAR(update_date) as year')
                        ->distinct()
                        ->pluck('year')
                        ->filter()
                        ->map(fn ($y) => (int) $y + 543);

                    return $fiscalYearValues->merge($legacyYearValues);
                },
            ],
            'hi' => [
                'title' => 'อัตราป่วยรายใหม่ HT',
                'subtitle' => null,
                'icon' => 'fa-heart-pulse',
                'color' => '#db2777',
                'real_years' => fn () => Hi::whereNotNull('year')->distinct()->pluck('year'),
            ],
            'kidney' => [
                'title' => 'รายการข้อมูล พชอ.ไต',
                'subtitle' => null,
                'icon' => 'fa-file-medical',
                'color' => '#f59e0b',
                'real_years' => fn () => KidneyAssessment::distinct()->pluck('fiscal_year'),
            ],
            'salt_assessment' => [
                'title' => 'แบบประเมินลดการบริโภคเกลือ',
                'subtitle' => null,
                'icon' => 'fa-clipboard-check',
                'color' => '#6366f1',
                'real_years' => fn () => SaltAssessment::distinct()->pluck('fiscal_year'),
            ],
        ];
    }

    public function index()
    {
        $modules = [];

        foreach ($this->moduleDefinitions() as $key => $def) {
            $realYearStrings = $def['real_years']()
                ->filter(fn ($y) => $y !== null && $y !== '')
                ->map(fn ($y) => (string) (int) $y)
                ->unique();

            $rows = FiscalYear::where('module', $key)->get();
            $enabledRows = $rows->where('hidden', false)->keyBy(fn ($r) => (string) $r->year);
            $hiddenRows = $rows->where('hidden', true)->keyBy(fn ($r) => (string) $r->year);

            $years = $realYearStrings->merge($enabledRows->keys())
                ->merge($hiddenRows->keys())
                ->unique()
                ->sortDesc()
                ->values()
                ->map(function ($y) use ($realYearStrings, $enabledRows, $hiddenRows) {
                    return [
                        'year' => $y,
                        'has_data' => $realYearStrings->contains($y),
                        'is_hidden' => $hiddenRows->has($y),
                        'fiscal_year_id' => optional($enabledRows->get($y) ?? $hiddenRows->get($y))->id,
                    ];
                });

            $modules[] = [
                'key' => $key,
                'title' => $def['title'],
                'subtitle' => $def['subtitle'],
                'icon' => $def['icon'],
                'color' => $def['color'],
                'years' => $years,
            ];
        }

        return view('admin.fiscal-years.index', compact('modules'));
    }

    /**
     * Add (enable) or hide one fiscal year for one module, depending on
     * `hidden` - idempotent either way (updateOrCreate), so re-submitting a
     * year that already has the requested state is not an error, and
     * flipping a year from one state to the other (e.g. an accidental hide,
     * undone by adding the same year back) just updates the same row.
     */
    public function store(Request $request)
    {
        $request->validate([
            'module' => 'required|in:' . implode(',', array_keys(FiscalYear::MODULES)),
            'year' => 'required|integer|min:2500|max:2700',
            'hidden' => 'nullable|boolean',
        ], [
            'module.required' => 'ไม่พบหัวข้อที่ต้องการเพิ่มปีงบประมาณ',
            'module.in' => 'หัวข้อไม่ถูกต้อง',
            'year.required' => 'กรุณาระบุปีงบประมาณ',
            'year.integer' => 'ปีงบประมาณต้องเป็นตัวเลข',
            'year.min' => 'ปีงบประมาณไม่ถูกต้อง (ต้องอยู่ระหว่าง 2500-2700)',
            'year.max' => 'ปีงบประมาณไม่ถูกต้อง (ต้องอยู่ระหว่าง 2500-2700)',
        ]);

        $hidden = $request->boolean('hidden');

        try {
            $fiscalYear = FiscalYear::updateOrCreate(
                [
                    'module' => $request->input('module'),
                    'year' => (int) $request->input('year'),
                ],
                ['hidden' => $hidden]
            );

            return response()->json([
                'success' => true,
                'message' => $hidden
                    ? 'ซ่อนปีงบประมาณ ' . $fiscalYear->year . ' จากตัวกรอง/อัปโหลดเรียบร้อยแล้ว'
                    : 'เพิ่มปีงบประมาณ ' . $fiscalYear->year . ' เรียบร้อยแล้ว',
                'fiscal_year_id' => $fiscalYear->id,
                'year' => (string) $fiscalYear->year,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove one fiscal_years row, whichever kind it is:
     *  - an "enabled" row (hidden=false) - the year disappears from the
     *    dropdown unless it also has real data (see selectableYearsFor()).
     *  - a "hidden" row (hidden=true) - the year becomes selectable again,
     *    since it was never actually removed from the data, only hidden.
     * Never touches any other table - this can only ever change what shows
     * up in a dropdown, never the underlying data itself.
     */
    public function destroy($id)
    {
        try {
            $fiscalYear = FiscalYear::findOrFail($id);
            $year = $fiscalYear->year;
            $wasHidden = $fiscalYear->hidden;
            $fiscalYear->delete();

            return response()->json([
                'success' => true,
                'message' => $wasHidden
                    ? 'ยกเลิกการซ่อนปีงบประมาณ ' . $year . ' เรียบร้อยแล้ว'
                    : 'นำปีงบประมาณ ' . $year . ' ออกจากรายการเรียบร้อยแล้ว',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
            ], 500);
        }
    }
}