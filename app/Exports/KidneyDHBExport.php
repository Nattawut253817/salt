<?php

namespace App\Exports;

use App\Models\KidneyAssessment;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Http\Request;

class KidneyDHBExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithColumnWidths
{
    use Exportable;

    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function query()
    {
        $user = auth()->user();
        $query = KidneyAssessment::query()->with(['user.province', 'user.district']);
        // รูปภาพประกอบ (ตาราง kidney_assessment_images) - โหลดล่วงหน้าเพื่อใส่ลิงก์รูปในคอลัมน์ท้ายตาราง
        if (\App\Services\KidneyImageService::available()) {
            $query->with('images');
        }

        // Data Isolation
        if ($user->User_rank_id != 1) {
            $query->where('user_id', $user->id);
        }

        // Apply Filters
        if ($this->request->filled('fiscal_year')) {
            $query->where('fiscal_year', $this->request->fiscal_year);
        }
        if ($this->request->filled('quarter')) {
            $query->where('quarter', $this->request->quarter);
        }
        if ($this->request->filled('province_id')) {
            $query->whereHas('user', function ($q) {
                $q->where('Province_id', $this->request->province_id);
            });
        }
        if ($this->request->filled('district_id')) {
            $query->whereHas('user', function ($q) {
                $q->where('District_id', $this->request->district_id);
            });
        }

        return $query->orderBy('user_id')->orderBy('fiscal_year', 'desc')->orderBy('quarter', 'asc');
    }

    public function headings(): array
    {
        return [
            'ปีงบประมาณ',
            'ไตรมาส',
            'หน่วยงาน',
            'หมวด 1',
            'หมวด 2',
            'หมวด 3',
            'หมวด 4',
            'หมวด 5',
            'หมวด 6',
            'หมวด 7',
            'หมวด 8.1',
            'หมวด 8.2',
            'หมวด 8.3',
            'ปัญหา อุปสรรค',
            'ข้อเสนอแนะ/โอกาสพัฒนา',
            'วันที่บันทึก',
            // รูปภาพประกอบ (ลิงก์ไฟล์รูป สูงสุด 3 รูปต่อข้อ - 1 บรรทัดต่อ 1 รูป)
            'รูปภาพ หมวด 1',
            'รูปภาพ หมวด 2',
            'รูปภาพ หมวด 3',
            'รูปภาพ หมวด 4',
            'รูปภาพ หมวด 5',
            'รูปภาพ หมวด 6',
            'รูปภาพ หมวด 7',
            'รูปภาพ หมวด 8.1',
            'รูปภาพ หมวด 8.2',
            'รูปภาพ หมวด 8.3',
        ];
    }

    /** คอลัมน์รูปภาพ (Q..Z) กว้างคงที่ + ตัดบรรทัด เพราะเป็นลิงก์ยาว */
    public function columnWidths(): array
    {
        return array_fill_keys(['Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'], 42);
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold
            1 => ['font' => ['bold' => true]],

            // Style the rest of the rows as normal (not bold)
            'A2:Z' . $sheet->getHighestRow() => ['font' => ['bold' => false]],

            // ลิงก์รูปภาพหลายบรรทัดในช่องเดียว
            'Q2:Z' . $sheet->getHighestRow() => ['alignment' => ['wrapText' => true, 'vertical' => 'top']],
        ];
    }

    protected $resolvedCache = [];

    public function map($item): array
    {
        $agency = '-';
        if ($item->user) {
            if ($item->user->User_rank_id == 3) {
                $agency = 'สสอ.' . ($item->user->district->district_name ?? '');
            } elseif ($item->user->User_rank_id == 4) {
                $agency = 'รพ.สต.' . ($item->user->Con_name ?? '');
            } else {
                $agency = $item->user->name;
            }
        }

        // --- Dynamic Cumulative Resolution ---
        $cacheKey = $item->user_id . '_' . $item->fiscal_year;
        if (!isset($this->resolvedCache[$cacheKey])) {
            $this->resolvedCache[$cacheKey] = KidneyAssessment::where('user_id', $item->user_id)
                ->where('fiscal_year', $item->fiscal_year)
                ->orderBy('quarter', 'asc')
                ->get()
                ->groupBy('quarter');
        }

        $allQs = $this->resolvedCache[$cacheKey];
        $catFields = [
            'category_1',
            'category_2',
            'category_3',
            'category_4',
            'category_5',
            'category_6',
            'category_7',
            'category_8_1',
            'category_8_2',
            'category_8_3'
        ];

        $resolved = [];
        foreach ($catFields as $cat) {
            $winner = $item;
            $maxUpdate = $item->updated_at ? $item->updated_at->timestamp : 0;

            // Scan all available quarters for this user/year up to the current row's quarter
            foreach ($allQs as $q => $recs) {
                if ((int) $q <= (int) $item->quarter) {
                    $rec = $recs->first();
                    if (!empty($rec->$cat)) {
                        $time = $rec->updated_at ? $rec->updated_at->timestamp : 0;
                        if ($time >= $maxUpdate) {
                            $winner = $rec;
                            $maxUpdate = $time;
                        }
                    }
                }
            }
            $resolved[$cat] = $winner->$cat;
        }

        // รูปภาพประกอบของรายงานแถวนี้เอง (ไม่ไหลข้ามไตรมาส) - ลิงก์ละบรรทัด
        $imageCells = [];
        $byCat = $item->relationLoaded('images') ? $item->images->groupBy('category') : collect();
        foreach (['1', '2', '3', '4', '5', '6', '7', '8_1', '8_2', '8_3'] as $catKey) {
            $imageCells[] = $byCat->has($catKey)
                ? $byCat[$catKey]->sortBy('slot')->map(fn($img) => $img->url)->implode("\n")
                : '';
        }

        return array_merge([
            $item->fiscal_year,
            $item->quarter,
            $agency,
            $resolved['category_1'],
            $resolved['category_2'],
            $resolved['category_3'],
            $resolved['category_4'],
            $resolved['category_5'],
            $resolved['category_6'],
            $resolved['category_7'],
            $resolved['category_8_1'],
            $resolved['category_8_2'],
            $resolved['category_8_3'],
            trim($item->problems_obstacles),
            trim($item->recommendations_opportunities),
            $item->created_at ? $item->created_at->format('d/m/Y H:i') : '-'
        ], $imageCells);
    }
}
