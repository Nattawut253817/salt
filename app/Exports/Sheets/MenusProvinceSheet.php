<?php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithDrawings;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use App\Models\ReducedSodiumMenu;
use Illuminate\Support\Collection;

class MenusProvinceSheet implements FromCollection, WithTitle, WithHeadings, WithStyles, ShouldAutoSize, WithDrawings
{
    protected $provinceName;
    protected $filters;
    protected $menus;

    public function __construct($provinceName, $filters)
    {
        $this->provinceName = $provinceName;
        $this->filters = $filters;
    }

    public function title(): string
    {
        return mb_substr($this->provinceName, 0, 31);
    }

    public function headings(): array
    {
        return [
            [
                'ปีงบประมาณ',
                'ภาพเมนู',
                'ชื่อเมนู',
                'โซเดียมก่อน (มก.)',
                'โซเดียมหลัง (มก.)',
                'สถานที่จำหน่าย',
                'จังหวัด',
                'อำเภอ',
                'หน่วยงาน/แหล่งผลิต',
                'วันที่อัปเดต'
            ],
        ];
    }

    protected function getMenus()
    {
        if ($this->menus) {
            return $this->menus;
        }

        $query = ReducedSodiumMenu::where(\DB::raw('TRIM(province)'), $this->provinceName);

        if (!empty($this->filters['year'])) {
            $query->where(\DB::raw('TRIM(year)'), trim($this->filters['year']));
        }
        if (!empty($this->filters['kitchen_type'])) {
            $query->where(\DB::raw('TRIM(kitchen_type)'), trim($this->filters['kitchen_type']));
        }

        return $this->menus = $query->orderBy('update_date', 'desc')->get();
    }

    public function collection()
    {
        return $this->getMenus()->map(function ($menu) {
            $hasImage = false;
            if ($menu->product_image) {
                $imagePath = storage_path('app/public/' . $menu->product_image);
                if (file_exists($imagePath)) {
                    $hasImage = true;
                }
            }

            return [
                $menu->year, 
                $hasImage ? '' : '-', // Show '-' if no image
                $menu->menu_name,
                $menu->sodium_before,
                $menu->sodium_after,
                $menu->kitchen_type,
                $menu->province,
                $menu->district,
                $menu->org_name,
                $menu->update_date ? \Carbon\Carbon::parse($menu->update_date)->format('d/m/Y') : '-',
            ];
        });
    }

    public function drawings()
    {
        $drawings = [];
        foreach ($this->getMenus() as $index => $menu) {
            if ($menu->product_image) {
                $imagePath = storage_path('app/public/' . $menu->product_image);
                if (file_exists($imagePath)) {
                    $drawing = new Drawing();
                    $drawing->setName($menu->menu_name);
                    $drawing->setPath($imagePath);
                    $drawing->setHeight(90);
                    $drawing->setCoordinates('B' . ($index + 2));
                    $drawing->setOffsetX(20);
                    $drawing->setOffsetY(5);
                    $drawings[] = $drawing;
                }
            }
        }
        return $drawings;
    }

    public function styles(Worksheet $sheet)
    {
        $rowCount = count($this->getMenus());
        for ($i = 2; $i < 2 + $rowCount; $i++) {
            $sheet->getRowDimension($i)->setRowHeight(100);
        }

        $sheet->getColumnDimension('A')->setWidth(15); // ปีงบประมาณ
        $sheet->getColumnDimension('B')->setWidth(20); // ภาพ
        $sheet->getColumnDimension('C')->setWidth(40); // ชื่อเมนู
        $sheet->getColumnDimension('D')->setWidth(18); // ก่อน
        $sheet->getColumnDimension('E')->setWidth(18); // หลัง
        $sheet->getColumnDimension('F')->setWidth(25); // สถานที่
        $sheet->getColumnDimension('G')->setWidth(15); // จังหวัด
        $sheet->getColumnDimension('H')->setWidth(20); // อำเภอ
        $sheet->getColumnDimension('I')->setWidth(30); // หน่วยงาน
        $sheet->getColumnDimension('J')->setWidth(15); // วันที่

        // Alignment
        $sheet->getStyle('A2:J' . (1 + $rowCount))->getAlignment()->setVertical('center');
        $sheet->getStyle('A2:B' . (1 + $rowCount))->getAlignment()->setHorizontal('center');
        $sheet->getStyle('D2:E' . (1 + $rowCount))->getAlignment()->setHorizontal('center');
        $sheet->getStyle('J2:J' . (1 + $rowCount))->getAlignment()->setHorizontal('center');

        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => '000000']], 
                'alignment' => ['horizontal' => 'center']
            ],
        ];
    }
}
