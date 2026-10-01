<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * One row = one fiscal year an admin has explicitly overridden for one
 * module's search filters / Excel-upload dropdowns, via "จัดการปีงบประมาณ"
 * (FiscalYearController). See the fiscal_years + add-hidden migrations for
 * the full rationale. A row means one of two things, decided by `hidden`:
 *
 *  - hidden = false: "enable this year even though it has no data yet"
 *    (the original add-year feature - lets a brand new fiscal year be
 *    selectable ahead of time).
 *  - hidden = true: "hide this year even though it already has real data"
 *    (the opposite override - lets an admin remove a year from the
 *    dropdowns without touching the underlying data at all; if that data
 *    is later needed again, deleting the hidden row brings the year right
 *    back, since it was never actually gone).
 *
 * selectableYearsFor() is the single place that combines a module's real
 * data-years with both kinds of override - every controller that needs a
 * module's full selectable-years list calls it instead of re-deriving the
 * merge/hide/floor logic itself.
 */
class FiscalYear extends Model
{
    use HasFactory;

    protected $fillable = [
        'module',
        'year',
        'hidden',
    ];

    protected $casts = [
        'hidden' => 'boolean',
    ];

    // Fixed module keys this screen manages, and their Thai display titles -
    // keep in sync with FiscalYearController::index()'s $modules list.
    const MODULES = [
        'awareness' => 'การประเมินความตระหนักรู้',
        'sodium_menus' => 'เมนูลดโซเดียม',
        'sodium_products' => 'ผลิตภัณฑ์ลดโซเดียม',
        'hi' => 'อัตราป่วยรายใหม่ HT',
        'kidney' => 'รายการข้อมูล พชอ.ไต',
        'salt_assessment' => 'แบบประเมินลดการบริโภคเกลือ',
    ];

    /**
     * Every year explicitly enabled (not hidden) for this module, newest
     * first - i.e. years added even though they may have no data yet.
     */
    public static function yearsFor(string $module)
    {
        return static::where('module', $module)->where('hidden', false)->orderByDesc('year')->pluck('year');
    }

    /**
     * The full, final list of years selectable for one module's search
     * filter / Excel-upload dropdown: whatever years already have real
     * data (passed in as $realYears - each module's own controller already
     * knows how to query this), plus any admin-enabled years, minus any
     * admin-hidden years - always falling back to the current fiscal year
     * alone if that combination would otherwise be completely empty, so a
     * dropdown is never left with literally nothing to pick.
     */
    public static function selectableYearsFor(string $module, $realYears): Collection
    {
        $normalize = fn ($y) => (string) (int) $y;

        $real = collect($realYears)->filter(fn ($y) => $y !== null && $y !== '')->map($normalize);
        $enabled = static::where('module', $module)->where('hidden', false)->pluck('year')->map($normalize);
        $hidden = static::where('module', $module)->where('hidden', true)->pluck('year')->map($normalize);

        $years = $real->merge($enabled)
            ->unique()
            ->reject(fn ($y) => $hidden->contains($y))
            ->map(fn ($y) => (int) $y)
            ->sortDesc()
            ->values();

        return $years->isEmpty() ? collect([(int) date('Y') + 543]) : $years;
    }
}
