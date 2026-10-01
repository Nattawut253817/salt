<?php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Worksheet\MemoryDrawing;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;

/**
 * Sheet 1 of the "ผลิตภัณฑ์ลดโซเดียม" import template - the sheet
 * App\Imports\ReducedSodiumProductImport actually reads (it only ever
 * looks at the file's first/active sheet, same as the other importers in
 * this app, so this MUST stay sheet 1 - the instructions live on sheet 2).
 *
 * Just like the เมนูลดโซเดียม template, ปีงบประมาณ (A) and จังหวัด (B) are kept
 * here only for backward familiarity with an earlier version of this
 * template - the admin now picks both once for the whole file in the
 * import modal's Step 1, and App\Imports\ReducedSodiumProductImport never
 * reads columns A/B (reduced_sodium_products otherwise has neither a
 * fiscal year nor a province of its own - see the
 * add_fiscal_year_to_reduced_sodium_products_table and
 * add_province_name_to_reduced_sodium_products_table migrations). ประเภท
 * ผลิตภัณฑ์ (D) and มาตรฐาน/การรับรอง (G) remain real per-row dropdowns the
 * importer does read - none of the four columns cascades off another.
 *
 * ปริมาณโซเดียมก่อนปรับสูตร (E) and ปริมาณโซเดียมหลังปรับสูตร (F) are two
 * independent columns mirroring the before/after pair added to the
 * database by add_sodium_amount_before_to_reduced_sodium_products_table -
 * "หลังปรับสูตร" is what sodium_amount has always meant in this app.
 *
 * Column I ("รูปภาพสินค้า") works exactly like the menu template's image
 * column: paste or insert a picture into a data row's I cell and the
 * importer pulls it in as that row's product photo (see
 * AdminController::importSodiumProducts()). Column I's width (20) and the
 * data rows' height (110pt) are tuned to make the cell nearly square, and
 * a generated size-guide graphic is drawn into I2 at that same size.
 */
class SodiumProductTemplateDataSheet implements FromArray, WithTitle, WithStyles, WithDrawings, ShouldAutoSize
{
    /** Same 5 health-region provinces used throughout the admin pages. */
    private const PROVINCES = ['อุบลราชธานี', 'ศรีสะเกษ', 'ยโสธร', 'อำนาจเจริญ', 'มุกดาหาร'];

    /** Mirrors AdminController::sodiumProducts()'s $defaultTypes. */
    private const PRODUCT_TYPES = [
        'กลุ่มผลิตภัณฑ์จากพืชและผลไม้',
        'กลุ่มพืชผักและผลไม้หมักดอง',
        'กลุ่มเครื่องเทศและเครื่องปรุงรส',
        'กลุ่มเนื้อสัตว์หมัก',
        'กลุ่มเนื้อสัตว์แห้ง',
        'กลุ่มแป้ง',
    ];

    /** Mirrors AdminController::sodiumProducts()'s $defaultStandards. */
    private const STANDARDS = [
        'GHP / GMP',
        'HACCP',
        'มาตรฐาน อย.',
        'มาตรฐานผลิตภัณฑ์ชุมชน (มผช.)',
        'มาตรฐานผลิตภัณฑ์อินทรีย์',
        'มาตรฐานฮาลาล',
    ];

    /** Same range the เมนูลดโซเดียม import modal offers: this Buddhist year back to 2560. */
    private function fiscalYears(): array
    {
        return range((int) date('Y') + 543, 2560);
    }

    public function title(): string
    {
        return 'ข้อมูล';
    }

    public function array(): array
    {
        $currentYearBE = (int) date('Y') + 543;

        return [
            [
                'ปีงบประมาณ',
                'จังหวัด',
                'ชื่อผลิตภัณฑ์ *',
                'ประเภทผลิตภัณฑ์',
                'ปริมาณโซเดียมก่อนปรับสูตร (มก.)',
                'ปริมาณโซเดียมหลังปรับสูตร (มก.)',
                'มาตรฐาน/การรับรอง',
                'ผู้ผลิต/แหล่งผลิต',
                'รูปภาพสินค้า',
                'หมายเหตุ',
            ],
            [
                $currentYearBE,
                'อุบลราชธานี',
                'ปลาส้มลดโซเดียม (ตัวอย่าง - ลบแถวนี้ทิ้งได้)',
                'กลุ่มเนื้อสัตว์หมัก',
                1200,
                850,
                'มาตรฐาน อย.',
                'วิสาหกิจชุมชนบ้านตัวอย่าง',
                '',
                '',
            ],
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->freezePane('A2');
        for ($row = 2; $row <= 30; $row++) {
            $sheet->getRowDimension($row)->setRowHeight(110);
        }

        $sheet->getColumnDimension('A')->setWidth(16); // ปีงบประมาณ
        $sheet->getColumnDimension('B')->setWidth(18); // จังหวัด
        $sheet->getColumnDimension('C')->setWidth(36); // ชื่อผลิตภัณฑ์
        $sheet->getColumnDimension('D')->setWidth(28); // ประเภทผลิตภัณฑ์
        $sheet->getColumnDimension('E')->setWidth(20); // โซเดียมก่อนปรับสูตร
        $sheet->getColumnDimension('F')->setWidth(20); // โซเดียมหลังปรับสูตร
        $sheet->getColumnDimension('G')->setWidth(28); // มาตรฐาน
        $sheet->getColumnDimension('H')->setWidth(30); // ผู้ผลิต
        $sheet->getColumnDimension('I')->setWidth(20); // รูปภาพสินค้า - fixed
        $sheet->getColumnDimension('J')->setWidth(24); // หมายเหตุ

        $sheet->getStyle('A2:J2')->getFont()->setItalic(true)->getColor()->setRGB('94A3B8');
        $sheet->getStyle('A2:J30')->getAlignment()->setVertical('top');

        $sheet->getComment('I1')->getText()->createTextRun(
            "คลิกขวา > แทรกรูปภาพ หรือคัดลอกรูปภาพแล้ววางลงในเซลล์ของแถวนั้นๆ ได้เลย\n" .
            "แนะนำให้ปรับขนาดรูปให้ใกล้เคียงกรอบตัวอย่างสีม่วงอ่อนในแถวที่ 2 (ประมาณ 4x4 ซม. หรือสี่เหลี่ยมจัตุรัส) ก่อนวาง เพื่อให้ทุกแถวดูเป็นระเบียบ ไม่เล็กไม่ใหญ่เกินไป\n" .
            "ระบบจะดึงรูปไปเก็บเป็นรูปสินค้าให้อัตโนมัติตอนนำเข้าข้อมูล (รองรับเฉพาะไฟล์ .xlsx)"
        );
        $sheet->getComment('I1')->setWidth('280pt');
        $sheet->getComment('I1')->setHeight('120pt');

        $sheet->getComment('A1')->getText()->createTextRun(
            "ไม่ต้องกรอกก็ได้ - ระบบจะใช้ปีงบประมาณที่เลือกในหน้าต่างนำเข้าแทนค่าในคอลัมน์นี้เสมอ"
        );
        $sheet->getComment('B1')->getText()->createTextRun(
            "ไม่ต้องกรอกก็ได้ - ระบบจะใช้จังหวัดที่เลือกในหน้าต่างนำเข้าแทนค่าในคอลัมน์นี้เสมอ"
        );

        $this->applyDropdowns($sheet);

        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'color' => ['rgb' => '4F46E5'],
                ],
                'alignment' => ['vertical' => 'center', 'horizontal' => 'center'],
            ],
        ];
    }

    /**
     * ปีงบประมาณ (A), จังหวัด (B), ประเภทผลิตภัณฑ์ (D) and มาตรฐาน/การรับรอง
     * (G) are four independent dropdown lists - none of them cascades off
     * another, so (unlike the menu template's จังหวัด->อำเภอ pair) a plain
     * inline list formula is enough for each; no hidden lookup sheet needed.
     */
    private function applyDropdowns(Worksheet $sheet): void
    {
        $this->addListValidation($sheet, 'A', array_map('strval', $this->fiscalYears()), 'ปีงบประมาณไม่ถูกต้อง', 'กรุณาเลือกปีงบประมาณ (พ.ศ.) จากรายการที่กำหนดเท่านั้น');
        $this->addListValidation($sheet, 'B', self::PROVINCES, 'จังหวัดไม่ถูกต้อง', 'กรุณาเลือกจังหวัดจากรายการที่กำหนดเท่านั้น');
        $this->addListValidation($sheet, 'D', self::PRODUCT_TYPES, 'ประเภทผลิตภัณฑ์ไม่ถูกต้อง', 'กรุณาเลือกประเภทผลิตภัณฑ์จากรายการที่กำหนด หรือเว้นว่างไว้ได้');
        $this->addListValidation($sheet, 'G', self::STANDARDS, 'มาตรฐาน/การรับรองไม่ถูกต้อง', 'กรุณาเลือกมาตรฐาน/การรับรองจากรายการที่กำหนด หรือเว้นว่างไว้ได้');
    }

    private function addListValidation(Worksheet $sheet, string $column, array $options, string $errorTitle, string $errorMessage): void
    {
        $validation = new DataValidation();
        $validation->setType(DataValidation::TYPE_LIST);
        $validation->setErrorStyle(DataValidation::STYLE_STOP);
        $validation->setAllowBlank(true);
        $validation->setShowDropDown(true);
        $validation->setShowInputMessage(true);
        $validation->setShowErrorMessage(true);
        $validation->setErrorTitle($errorTitle);
        $validation->setError($errorMessage);
        $validation->setFormula1('"' . implode(',', $options) . '"');
        $validation->setSqref($column . '2:' . $column . '300');
        $sheet->setDataValidation($column . '2', $validation);
    }

    /**
     * Drops a generated "this is the size to aim for" placeholder into the
     * example row's image cell (I2) - a size guide only, deleting the
     * example row (or the picture itself) is fine, it never reaches the
     * importer.
     */
    public function drawings()
    {
        $drawing = new MemoryDrawing();
        $drawing->setName('ตัวอย่างขนาดรูปภาพ');
        $drawing->setDescription('กรอบตัวอย่างขนาดรูปภาพที่แนะนำ - ลบทิ้งได้ ไม่มีผลต่อการนำเข้าข้อมูล');
        $drawing->setImageResource($this->buildSizeGuideImage());
        $drawing->setRenderingFunction(MemoryDrawing::RENDERING_PNG);
        $drawing->setMimeType(MemoryDrawing::MIMETYPE_DEFAULT);
        $drawing->setCoordinates('I2');
        $drawing->setOffsetX(13);
        $drawing->setOffsetY(14);
        $drawing->setWidth(120);
        $drawing->setHeight(120);

        return [$drawing];
    }

    /**
     * Same GD-only (no TTF font) "photo frame" placeholder icon used by
     * the menu template, so both templates' image columns look consistent.
     *
     * @return \GdImage|resource
     */
    private function buildSizeGuideImage()
    {
        $size = 120;
        $im = imagecreatetruecolor($size, $size);

        $bg = imagecolorallocate($im, 238, 242, 255);     // #EEF2FF
        $border = imagecolorallocate($im, 165, 180, 252); // #A5B4FC
        $icon = imagecolorallocate($im, 99, 102, 241);    // #6366F1

        imagefilledrectangle($im, 0, 0, $size - 1, $size - 1, $bg);
        imagerectangle($im, 0, 0, $size - 1, $size - 1, $border);
        imagerectangle($im, 2, 2, $size - 3, $size - 3, $border);

        $pad = 22;
        imagerectangle($im, $pad, $pad, $size - $pad, $size - $pad, $icon);
        imagefilledellipse($im, $pad + 16, $pad + 16, 14, 14, $icon);
        imagefilledpolygon($im, [
            $pad + 4, $size - $pad - 4,
            $pad + 30, $size - $pad - 28,
            $pad + 46, $size - $pad - 12,
            $size - $pad - 4, $size - $pad - 26,
            $size - $pad - 4, $size - $pad - 4,
        ], $icon);

        return $im;
    }
}
