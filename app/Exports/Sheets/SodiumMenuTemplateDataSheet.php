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
use PhpOffice\PhpSpreadsheet\NamedRange;

/**
 * Sheet 1 of the "เมนูลดโซเดียม" import template - the sheet the importer
 * actually reads (App\Imports\ReducedSodiumMenuImportV2 always looks at
 * the file's first/active sheet, same as the legacy importer did, so this
 * MUST stay sheet 1 - the instructions live on sheet 2 instead).
 *
 * Column K ("รูปภาพเมนู") is the one functional addition over the old
 * template: paste or insert a picture into a data row's K cell and the
 * importer will pull it in as that row's product photo (see
 * AdminController::importSodiumMenus()). Columns A/B (ปีงบประมาณ/จังหวัด)
 * are kept for backward familiarity but are NOT read by the importer -
 * the fiscal year and province chosen in the import dialog always win,
 * so they can be left blank.
 *
 * Column K's width (20) and the data rows' height (110pt) are tuned to
 * make the cell nearly square (~145x147px), and a generated size-guide
 * graphic is drawn into K2 at that same size - so a user pasting in a
 * real photo has a visual target to resize to and every row ends up
 * looking consistent (not "too small, not too big") instead of whatever
 * size their source photo happened to be.
 */
class SodiumMenuTemplateDataSheet implements FromArray, WithTitle, WithStyles, WithDrawings, ShouldAutoSize
{
    public function title(): string
    {
        return 'ข้อมูล';
    }

    public function array(): array
    {
        return [
            [
                'ปีงบประมาณ',
                'จังหวัด',
                'อำเภอ',
                'ประเภทหน่วยงาน',
                'หน่วยงาน',
                'โรงครัวรพ./ร้านอาหารในรพ',
                'ชื่อเมนูอาหาร *',
                'โซเดียมก่อนปรับสูตร (มก.)',
                'โซเดียมหลังปรับสูตร (มก.)',
                'หมายเหตุ',
                'รูปภาพเมนู',
            ],
            [
                '(ไม่ต้องกรอก)',
                '(ไม่ต้องกรอก)',
                'เมืองอุบลราชธานี',
                'รพ.',
                'โรงพยาบาลสรรพสิทธิประสงค์',
                'โรงครัว',
                'ผัดฟักทองใส่ไข่ (ตัวอย่าง - ลบแถวนี้ทิ้งได้)',
                4953,
                2100,
                '',
                '',
            ],
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Freeze the header row so it stays visible while scrolling a
        // long sheet, and give the example row (and the next several
        // blank rows) enough height for a pasted-in photo.
        $sheet->freezePane('A2');
        for ($row = 2; $row <= 30; $row++) {
            $sheet->getRowDimension($row)->setRowHeight(110);
        }

        $sheet->getColumnDimension('A')->setWidth(14); // ปีงบประมาณ
        $sheet->getColumnDimension('B')->setWidth(16); // จังหวัด
        $sheet->getColumnDimension('C')->setWidth(18); // อำเภอ
        $sheet->getColumnDimension('D')->setWidth(18); // ประเภทหน่วยงาน
        $sheet->getColumnDimension('E')->setWidth(30); // หน่วยงาน
        $sheet->getColumnDimension('F')->setWidth(22); // โรงครัว/ร้านอาหาร
        $sheet->getColumnDimension('G')->setWidth(34); // ชื่อเมนูอาหาร
        $sheet->getColumnDimension('H')->setWidth(18); // โซเดียมก่อน
        $sheet->getColumnDimension('I')->setWidth(18); // โซเดียมหลัง
        $sheet->getColumnDimension('J')->setWidth(24); // หมายเหตุ
        $sheet->getColumnDimension('K')->setWidth(20); // รูปภาพเมนู - fixed

        $sheet->getStyle('A2:B2')->getFont()->setItalic(true)->getColor()->setRGB('94A3B8');
        $sheet->getStyle('C2:J2')->getFont()->setItalic(true)->getColor()->setRGB('64748B');
        $sheet->getStyle('A2:K30')->getAlignment()->setVertical('top');

        $sheet->getComment('K1')->getText()->createTextRun(
            "คลิกขวา > แทรกรูปภาพ หรือคัดลอกรูปภาพแล้ววางลงในเซลล์ของแถวนั้นๆ ได้เลย\n" .
            "แนะนำให้ปรับขนาดรูปให้ใกล้เคียงกรอบตัวอย่างสีม่วงอ่อนในแถวที่ 2 (ประมาณ 4x4 ซม. หรือสี่เหลี่ยมจัตุรัส) ก่อนวาง เพื่อให้ทุกแถวดูเป็นระเบียบ ไม่เล็กไม่ใหญ่เกินไป\n" .
            "ระบบจะดึงรูปไปเก็บเป็นรูปเมนูให้อัตโนมัติตอนนำเข้าข้อมูล (รองรับเฉพาะไฟล์ .xlsx)"
        );
        $sheet->getComment('K1')->setWidth('280pt');
        $sheet->getComment('K1')->setHeight('120pt');

        $sheet->getComment('A1')->getText()->createTextRun(
            "ไม่ต้องกรอกก็ได้ - ระบบจะใช้ปีงบประมาณที่เลือกในหน้าต่างนำเข้าแทนค่าในคอลัมน์นี้เสมอ"
        );
        $sheet->getComment('B1')->getText()->createTextRun(
            "ไม่ต้องกรอกก็ได้ - ระบบจะใช้จังหวัดที่เลือกในหน้าต่างนำเข้าแทนค่าในคอลัมน์นี้เสมอ"
        );

        $this->applyProvinceDistrictDropdowns($sheet);

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
     * The 5 health-region provinces and their real amphoe (ตาม District
     * table ที่ระบบใช้อยู่แล้ว - see AuthController/User for the other place
     * it's read from), in the order the user asked for: อุบลราชธานี,
     * ศรีสะเกษ, อำนาจเจริญ, ยโสธร, มุกดาหาร.
     */
    private function provinceDistricts(): array
    {
        return [
            'อุบลราชธานี' => [
                'เมืองอุบลราชธานี', 'ศรีเมืองใหม่', 'โขงเจียม', 'เขื่องใน', 'เขมราฐ', 'เดชอุดม',
                'นาจะหลวย', 'น้ำยืน', 'บุณฑริก', 'ตระการพืชผล', 'กุดข้าวปุ้น', 'ม่วงสามสิบ',
                'วารินชำราบ', 'พิบูลมังสาหาร', 'ตาลสุม', 'โพธิ์ไทร', 'สำโรง', 'ดอนมดแดง',
                'สิรินธร', 'ทุ่งศรีอุดม', 'นาเยีย', 'นาตาล', 'เหล่าเสือโก้ก', 'สว่างวีระวงศ์', 'น้ำขุ่น',
            ],
            'ศรีสะเกษ' => [
                'เมือง', 'ยางชุมน้อย', 'กันทรารมย์', 'กันทรลักษ์', 'ขุขันธ์', 'ไพรบึง', 'ปรางค์กู่',
                'ขุนหาญ', 'ราษีไศล', 'อุทุมพรพิสัย', 'บึงบูรพ์', 'ห้วยทับทัน', 'โนนคูณ', 'ศรีรัตนะ',
                'น้ำเกลี้ยง', 'วังหิน', 'ภูสิงห์', 'เมืองจันทร์', 'เบญจลักษ์', 'พยุห์', 'โพธิ์ศรีสุวรรณ', 'ศิลาลาด',
            ],
            'อำนาจเจริญ' => [
                'เมืองอำนาจเจริญ', 'ชานุมาน', 'ปทุมราชวงศา', 'พนา', 'เสนางคนิคม', 'หัวตะพาน', 'ลืออำนาจ',
            ],
            'ยโสธร' => [
                'เมือง', 'ทรายมูล', 'กุดชุม', 'คำเขื่อนแก้ว', 'ป่าติ้ว', 'มหาชนะชัย', 'ค้อวัง', 'เลิงนกทา', 'ไทยเจริญ',
            ],
            'มุกดาหาร' => [
                'เมืองมุกดาหาร', 'นิคมคำสร้อย', 'ดอนตาล', 'ดงหลวง', 'คำชะอี', 'หว้านใหญ่', 'หนองสูง',
            ],
        ];
    }

    /**
     * Builds the จังหวัด -> อำเภอ cascading dropdown: a hidden lookup sheet
     * holds the 5 provinces (row 1) with each one's districts listed below
     * it, a named range per province (named after the province itself)
     * covers that district list, column B gets a plain dropdown of the 5
     * provinces, and column C's dropdown formula is `INDIRECT($B{row})` -
     * Excel resolves that to whichever named range matches the province
     * just picked in that same row, so the อำเภอ choices always match the
     * จังหวัด already selected.
     */
    private function applyProvinceDistrictDropdowns(Worksheet $sheet): void
    {
        $spreadsheet = $sheet->getParent();
        if (!$spreadsheet) {
            return;
        }

        $provinceDistricts = $this->provinceDistricts();
        $columns = ['A', 'B', 'C', 'D', 'E'];

        // Hidden helper sheet - never shown in the tab bar, and irrelevant
        // to the importer (which only ever reads this sheet, index 0).
        $lookup = $spreadsheet->createSheet();
        $lookup->setTitle('รายชื่ออำเภอ');
        $lookup->setSheetState(Worksheet::SHEETSTATE_VERYHIDDEN);

        $col = 0;
        foreach ($provinceDistricts as $province => $districts) {
            $letter = $columns[$col];
            $lookup->setCellValue($letter . '1', $province);
            foreach ($districts as $i => $district) {
                $lookup->setCellValue($letter . ($i + 2), $district);
            }

            $spreadsheet->addNamedRange(new NamedRange(
                $province,
                $lookup,
                '$' . $letter . '$2:$' . $letter . '$' . (count($districts) + 1)
            ));

            $col++;
        }

        // Column B (จังหวัด): fixed list of the 5 provinces, straight off
        // the lookup sheet's header row.
        $provinceValidation = new DataValidation();
        $provinceValidation->setType(DataValidation::TYPE_LIST);
        $provinceValidation->setErrorStyle(DataValidation::STYLE_STOP);
        $provinceValidation->setAllowBlank(true);
        $provinceValidation->setShowDropDown(true);
        $provinceValidation->setShowInputMessage(true);
        $provinceValidation->setShowErrorMessage(true);
        $provinceValidation->setErrorTitle('จังหวัดไม่ถูกต้อง');
        $provinceValidation->setError('กรุณาเลือกจังหวัดจากรายการที่กำหนดเท่านั้น');
        $provinceValidation->setPromptTitle('เลือกจังหวัด');
        $provinceValidation->setPrompt('เลือกจังหวัดจากรายการ (คอลัมน์นี้ระบบไม่ได้ใช้งาน แต่ช่วยให้เลือกอำเภอในคอลัมน์ถัดไปได้ถูกต้อง)');
        $provinceValidation->setFormula1("'รายชื่ออำเภอ'!\$A\$1:\$E\$1");
        $provinceValidation->setSqref('B2:B300');
        $sheet->setDataValidation('B2', $provinceValidation);

        // Column C (อำเภอ): cascades off whatever was picked in that same
        // row's จังหวัด cell (B). Excel adjusts the $B2 reference per row
        // across the whole sqref, same as applying validation via the UI.
        $districtValidation = new DataValidation();
        $districtValidation->setType(DataValidation::TYPE_LIST);
        $districtValidation->setErrorStyle(DataValidation::STYLE_STOP);
        $districtValidation->setAllowBlank(true);
        $districtValidation->setShowDropDown(true);
        $districtValidation->setShowInputMessage(true);
        $districtValidation->setShowErrorMessage(true);
        $districtValidation->setErrorTitle('อำเภอไม่ถูกต้อง');
        $districtValidation->setError('กรุณาเลือกจังหวัดในคอลัมน์ B ก่อน แล้วจึงเลือกอำเภอจากรายการที่ตรงกับจังหวัดนั้น');
        $districtValidation->setPromptTitle('เลือกอำเภอ');
        $districtValidation->setPrompt('เลือกจังหวัดในคอลัมน์ B ก่อน แล้วรายการอำเภอในช่องนี้จะเปลี่ยนตามจังหวัดที่เลือกอัตโนมัติ');
        $districtValidation->setFormula1('INDIRECT($B2)');
        $districtValidation->setSqref('C2:C300');
        $sheet->setDataValidation('C2', $districtValidation);
    }

    /**
     * Drops a generated "this is the size to aim for" placeholder into the
     * example row's image cell (K2), sized and centered to match column K's
     * width (20 -> ~145px) and the data rows' height (110pt -> ~147px) set
     * above. It's a size guide only - deleting the example row (or the
     * picture itself) is fine, it never reaches the importer.
     */
    public function drawings()
    {
        $drawing = new MemoryDrawing();
        $drawing->setName('ตัวอย่างขนาดรูปภาพ');
        $drawing->setDescription('กรอบตัวอย่างขนาดรูปภาพที่แนะนำ - ลบทิ้งได้ ไม่มีผลต่อการนำเข้าข้อมูล');
        $drawing->setImageResource($this->buildSizeGuideImage());
        $drawing->setRenderingFunction(MemoryDrawing::RENDERING_PNG);
        $drawing->setMimeType(MemoryDrawing::MIMETYPE_DEFAULT);
        $drawing->setCoordinates('K2');
        $drawing->setOffsetX(13);
        $drawing->setOffsetY(14);
        $drawing->setWidth(120);
        $drawing->setHeight(120);

        return [$drawing];
    }

    /**
     * Draws a small square "photo frame" icon with GD only (no TTF font,
     * so it renders the same regardless of what fonts the server has) -
     * a soft indigo frame that visually matches the app's own color
     * scheme, sized to sit neatly inside the K2 cell as a resize target.
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

        // Simple photo glyph: an inner frame with a "sun" and a "mountain"
        // - the universal placeholder-image icon - centered in the square.
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
