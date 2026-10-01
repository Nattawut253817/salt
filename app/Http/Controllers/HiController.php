<?php

namespace App\Http\Controllers;

use App\Models\Hi;
use App\Models\Province;
use App\Models\FiscalYear;
use App\Imports\HiImport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class HiController extends Controller
{
    public function index(Request $request)
    {
        $provinces = Province::orderBy('province_name')->get();

        $query = Hi::with('province');

        if ($request->filled('year')) {
            $query->where('year', $request->year);
        }

        if ($request->filled('province_id')) {
            $query->where('Province_id', (int) $request->province_id);
        }

        if ($request->filled('district')) {
            $query->where('District_name', 'like', '%' . trim($request->district) . '%');
        }

        // Month filter: this table stores one row per district/year with a
        // separate column for each fiscal month, so "filter by month" means
        // "only show rows that have data recorded for that month".
        $validMonthColumns = [
            'm10_oct', 'm11_nov', 'm12_dec', 'm01_jan', 'm02_feb', 'm03_mar',
            'm04_apr', 'm05_may', 'm06_jun', 'm07_jul', 'm08_aug', 'm09_sep',
        ];
        if ($request->filled('month') && in_array($request->month, $validMonthColumns, true)) {
            $query->whereNotNull($request->month);
        }

        if ($request->filled('search')) {
            $query->where('District_name', 'like', '%' . trim($request->search) . '%');
        }

        // Calculate Summary Statistics (on filtered results)
        $statsQuery = clone $query;
        $totalTarget = $statsQuery->sum('target_b');
        $totalNewCases = $statsQuery->sum('total_a');
        $avgRate = $statsQuery->avg('rate_per_100k') ?: 0;

        // Province-wise counts for the 5 target provinces
        $targetProvinces = ['อุบลราชธานี', 'ศรีสะเกษ', 'ยโสธร', 'อำนาจเจริญ', 'มุกดาหาร'];
        $provinceCounts = [];
        foreach ($targetProvinces as $pName) {
            $provinceCounts[$pName] = (clone $statsQuery)->whereHas('province', function ($q) use ($pName) {
                $q->where('province_name', $pName);
            })->count();
        }

        // Monthly totals across every filtered row (not just the current
        // page), used to render an "overview" summary row on the table with
        // the same high/low + month-over-month highlighting as individual
        // district rows. Cloned from $statsQuery the same way the counts
        // above are, so it reflects the exact same filtered scope.
        $monthlyTotals = (clone $statsQuery)->without('province')->selectRaw(
            implode(', ', array_map(fn ($col) => "SUM($col) as $col", $validMonthColumns))
        )->first();

        $stats = [
            'total_target' => $totalTarget,
            'total_new_cases' => $totalNewCases,
            'avg_rate' => $avgRate,
            'province_counts' => $provinceCounts,
            'monthly_totals' => $monthlyTotals,
        ];

        $hiData = $query->orderBy('year', 'desc')
            ->orderBy('District_name', 'asc')
            ->paginate(15);

        if ($request->ajax()) {
            return view('admin.hi.table', compact('hiData', 'stats'))->render();
        }

        // ปีงบประมาณ options for the filter dropdown AND the "นำเข้า Excel"
        // modal - real distinct years already in the data, plus any
        // admin-enabled years and minus any admin-hidden years from
        // "จัดการปีงบประมาณ" (FiscalYearController).
        $years = FiscalYear::selectableYearsFor('hi', Hi::whereNotNull('year')->distinct()->pluck('year'));

        return view('admin.hi.index', compact('provinces', 'hiData', 'stats', 'years'));
    }

    public function import(Request $request)
    {
        $request->validate([
            'year' => 'required',
            'province_id' => 'required',
            'file' => 'required|mimes:xlsx,xls,csv',
            'duplicate_action' => 'required|in:skip,replace',
        ], [
            'year.required' => 'กรุณาเลือกปีงบประมาณ',
            'province_id.required' => 'กรุณาเลือกจังหวัด',
            'file.required' => 'กรุณาเลือกไฟล์ Excel',
            'file.mimes' => 'รูปแบบไฟล์ต้องเป็น xlsx, xls หรือ csv เท่านั้น',
            'duplicate_action.required' => 'กรุณาเลือกการจัดการข้อมูลซ้ำ',
        ]);

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            $import = new HiImport($request->year, $request->province_id, $request->duplicate_action);
            Excel::import($import, $request->file('file'));

            \Illuminate\Support\Facades\DB::commit();

            return back()->with('import_result', [
                'imported' => $import->getImportedCount(),
                'updated'  => $import->getUpdatedCount(),
                'skipped'  => $import->getSkippedCount(),
                'replaced' => $import->getReplacedCount(),
                'mode'     => $request->duplicate_action,
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return back()->with('error', 'เกิดข้อผิดพลาดในการนำเข้าข้อมูล: ' . $e->getMessage());
        }
    }

    public function deleteFiltered(Request $request)
    {
        try {
            $query = Hi::query();

            // Apply the same filters as in index method
            if ($request->filled('year')) {
                $query->where('year', $request->year);
            }

            if ($request->filled('province_id')) {
                $query->where('Province_id', (int) $request->province_id);
            }

            if ($request->filled('district')) {
                $query->where('District_name', 'like', '%' . trim($request->district) . '%');
            }

            if ($request->filled('search')) {
                $query->where('District_name', 'like', '%' . trim($request->search) . '%');
            }

            $count = $query->count();

            if ($count === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'ไม่พบข้อมูลที่ตรงกับเงื่อนไขการกรอง'
                ], 404);
            }

            $query->delete();

            return response()->json([
                'success' => true,
                'message' => 'ลบข้อมูลสำเร็จ จำนวน ' . $count . ' รายการ',
                'deleted_count' => $count
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
            ], 500);
        }
    }
}
