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
use App\Models\ReducedSodiumProduct;
use Illuminate\Support\Collection;

class SodiumProductProvinceSheet implements FromCollection, WithTitle, WithHeadings, WithStyles, ShouldAutoSize, WithDrawings
{
    protected $provinceName;
    protected $filters;
    protected $products;

    public function __construct($provinceName, $filters)
    {
        $this->provinceName = $provinceName;
        $this->filters = $filters;
    }

    public function title(): string
    {
        // Sheet title limit is 31 chars
        return mb_substr($this->provinceName, 0, 31);
    }

    public function headings(): array
    {
        return [
            [
                'ปีงบประมาณ',
                'ภาพ',
                'ชื่อผลิตภัณฑ์',
                'ประเภทผลิตภัณฑ์',
                'ปริมาณโซเดียมก่อนปรับสูตร (มก.)',
                'ปริมาณโซเดียมหลังปรับสูตร (มก.)',
                'มาตรฐาน/การรับรอง',
                'ผู้ผลิต/แหล่งผลิต',
                'วันที่อัปเดต'
            ],
        ];
    }

    protected function getProducts()
    {
        if ($this->products) {
            return $this->products;
        }

        // A product's province may live on its own province_name column
        // (set by the "นำเข้า Excel" bulk importer - see
        // ReducedSodiumProductImport, since reduced_sodium_products has no
        // province of its own otherwise) OR only be reachable through the
        // owning user's account (every product added the original way).
        // Match either, same as AdminController::scopeProductsByProvinceName().
        $query = ReducedSodiumProduct::with('user.province')
            ->where(function ($q) {
                $q->where('province_name', $this->provinceName)
                    ->orWhereHas('user.province', function ($q2) {
                        $q2->where('province_name', $this->provinceName);
                    });
            });

        // Apply shared filters. $this->filters['year'] is a Buddhist year
        // (see AdminController::scopeProductsByFiscalYear() - matches
        // either the row's own fiscal_year or, for anything added before
        // that column existed, update_date's Gregorian year).
        if (!empty($this->filters['year'])) {
            $yearBE = (int) $this->filters['year'];
            $yearAD = $yearBE - 543;
            $query->where(function ($q) use ($yearBE, $yearAD) {
                $q->where('fiscal_year', $yearBE)
                    ->orWhere(function ($q2) use ($yearAD) {
                        $q2->where(function ($q3) {
                            $q3->whereNull('fiscal_year')->orWhere('fiscal_year', 0);
                        })->whereYear('update_date', $yearAD);
                    });
            });
        }
        if (!empty($this->filters['product_type'])) {
            $query->where('product_type', $this->filters['product_type']);
        }
        if (!empty($this->filters['standard'])) {
            $query->where('standard_certification', $this->filters['standard']);
        }

        return $this->products = $query->orderBy('update_date', 'desc')->get();
    }

    public function collection()
    {
        return $this->getProducts()->map(function ($product) {
            $yearBE = $product->fiscal_year
                ?: ($product->update_date ? (int)$product->update_date->format('Y') + 543 : '-');
            
            $hasImage = false;
            if ($product->product_image) {
                $imagePath = storage_path('app/public/' . $product->product_image);
                if (file_exists($imagePath)) {
                    $hasImage = true;
                }
            }

            return [
                $yearBE, // A: ปีงบประมาณ (พ.ศ.)
                $hasImage ? '' : '-', // B: ภาพผลิตภัณฑ์
                $product->product_name,
                $product->product_type,
                $product->sodium_amount_before,
                $product->sodium_amount,
                $product->standard_certification,
                $product->manufacturer_name,
                $product->update_date ? $product->update_date->format('d/m/Y') : '-',
            ];
        });
    }

    public function drawings()
    {
        $drawings = [];

        foreach ($this->getProducts() as $index => $product) {
            if ($product->product_image) {
                $imagePath = storage_path('app/public/' . $product->product_image);
                if (file_exists($imagePath)) {
                    $drawing = new Drawing();
                    $drawing->setName($product->product_name);
                    $drawing->setDescription($product->product_name);
                    $drawing->setPath($imagePath);
                    $drawing->setHeight(90);
                    $drawing->setCoordinates('B' . ($index + 2));
                    $drawing->setOffsetX(20); // Center in tighter column
                    $drawing->setOffsetY(5);  // Center vertically (For 100 row height)
                    $drawings[] = $drawing;
                }
            }
        }

        return $drawings;
    }

    public function styles(Worksheet $sheet)
    {
        // Set row height for data rows
        $rowCount = count($this->getProducts());
        for ($i = 2; $i < 2 + $rowCount; $i++) {
            $sheet->getRowDimension($i)->setRowHeight(100);
        }

        // Set specific column widths
        $sheet->getColumnDimension('A')->setWidth(15); // ปีงบประมาณ
        $sheet->getColumnDimension('B')->setWidth(20); // ภาพ (Perfect fit for 90px height)
        $sheet->getColumnDimension('C')->setWidth(45); // ชื่อผลิตภัณฑ์
        $sheet->getColumnDimension('D')->setWidth(25); // ประเภท
        $sheet->getColumnDimension('E')->setWidth(20); // โซเดียม
        $sheet->getColumnDimension('F')->setWidth(25); // มาตรฐาน
        $sheet->getColumnDimension('G')->setWidth(35); // ผู้ผลิต
        $sheet->getColumnDimension('H')->setWidth(15); // วันที่อัปเดต

        // Center alignment for all cells
        $sheet->getStyle('A2:H' . (1 + $rowCount))->getAlignment()->setVertical('center');
        $sheet->getStyle('A2:B' . (1 + $rowCount))->getAlignment()->setHorizontal('center');
        $sheet->getStyle('E2:E' . (1 + $rowCount))->getAlignment()->setHorizontal('center');
        $sheet->getStyle('H2:H' . (1 + $rowCount))->getAlignment()->setHorizontal('center');
        
        // Vertical center for all data cells
        $sheet->getStyle('A2:H' . (1 + $rowCount))->getAlignment()->setVertical('center');

        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => '000000']], 
                'alignment' => ['horizontal' => 'center']
            ],
        ];
    }
}
