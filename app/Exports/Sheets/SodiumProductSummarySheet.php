<?php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Collection;

class SodiumProductSummarySheet implements FromCollection, WithTitle, WithHeadings, WithStyles, ShouldAutoSize
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
            ['รายงานสรุปภาพรวมผลิตภัณฑ์ลดโซเดียม'],
            ['เขตสุขภาพที่ 10'],
            [''],
            ['หัวข้อการจัดทำรายงาน', 'จำนวน/ผลสรุป'],
        ];
    }

    public function collection()
    {
        $data = [
            ['จำนวนผลิตภัณฑ์ทั้งหมด', $this->stats['total'] . ' รายการ'],
            ['ได้รับรองสัญลักษณ์ทางเลือกสุขภาพ (Health Choice)', $this->stats['certified'] . ' รายการ'],
            ['ปริมาณโซเดียมเฉลี่ยทั้งหมด', number_format($this->stats['avg_sodium'], 2) . ' มิลลิกรัม'],
        ];

        // Add detailed standards
        if (!empty($this->stats['standards'])) {
            $data[] = [''];
            $data[] = ['รายละเอียดการรับรองมาตรฐาน (แยกประเภท)', ''];
            foreach ($this->stats['standards'] as $standard => $count) {
                if ($standard != 'เลือกมาตรฐาน/การรับรอง') {
                    $name = $standard ?: 'ไม่ระบุมาตรฐาน';
                    $data[] = [$name, $count . ' รายการ'];
                }
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
