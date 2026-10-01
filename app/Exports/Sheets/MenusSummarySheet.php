<?php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Collection;

class MenusSummarySheet implements FromCollection, WithTitle, WithHeadings, WithStyles, ShouldAutoSize
{
    protected $stats;

    public function __construct($stats)
    {
        $this->stats = $stats;
    }

    public function title(): string
    {
        return 'ภาพรวม';
    }

    public function headings(): array
    {
        return [
            ['รายงานสรุปภาพรวมเมนูลดโซเดียม'],
            ['เขตสุขภาพที่ 10'],
            [''],
            ['หัวข้อการจัดทำรายงาน', 'จำนวน/ผลสรุป'],
        ];
    }

    public function collection()
    {
        $data = [
            ['จำนวนเมนูทั้งหมด', $this->stats['total'] . ' รายการ'],
            ['ปริมาณโซเดียมเฉลี่ย (ก่อนปรับสูตร)', number_format($this->stats['avg_before'], 2) . ' มิลลิกรัม'],
            ['ปริมาณโซเดียมเฉลี่ย (หลังปรับสูตร)', number_format($this->stats['avg_after'], 2) . ' มิลลิกรัม'],
            ['การลดโซเดียมเฉลี่ย (%)', number_format($this->stats['avg_reduction'], 2) . ' %'],
        ];

        // Add province counts
        if (!empty($this->stats['province_counts'])) {
            $data[] = [''];
            $data[] = ['สรุปจำนวนข้อมูลแยกตามจังหวัด (ในเขตสุขภาพที่ 10)', ''];
            foreach ($this->stats['province_counts'] as $province => $count) {
                $name = $province ?: 'ไม่ระบุจังหวัด';
                $data[] = [$name, $count . ' รายการ'];
            }
        }

        return new Collection($data);
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->mergeCells('A1:B1');
        $sheet->mergeCells('A2:B2');
        
        $sheet->getColumnDimension('A')->setWidth(50);
        $sheet->getColumnDimension('B')->setWidth(30);

        return [
            1 => ['font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => '000000']]],
            2 => ['font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => '000000']]],
            4 => [
                'font' => ['bold' => true, 'color' => ['rgb' => '000000']], 
                'alignment' => ['horizontal' => 'center']
            ],
        ];
    }
}
