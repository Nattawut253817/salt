<?php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Sheet 2 - plain reference text, kept OFF the data sheet on purpose:
 * App\Imports\ReducedSodiumMenuImportV2 (like the importer before it)
 * only reads the file's first/active sheet, so anything here is purely
 * for a human reading the file in Excel and never touches the import.
 */
class SodiumMenuTemplateInstructionsSheet implements FromArray, WithTitle, WithStyles
{
    public function title(): string
    {
        return 'คำแนะนำ';
    }

    public function array(): array
    {
        return [
            ['วิธีใช้ไฟล์ต้นแบบนี้'],
            [''],
            ['1. กรอกข้อมูลลงในชีท "ข้อมูล" เริ่มตั้งแต่แถวที่ 2 เป็นต้นไป (ลบแถวตัวอย่างในแถวที่ 2 ทิ้งได้)'],
            ['2. ไม่ต้องกรอกคอลัมน์ "ปีงบประมาณ" และ "จังหวัด" ก็ได้ - ระบบจะใช้ค่าที่เลือกในหน้าต่างนำเข้าข้อมูลแทนค่าในไฟล์เสมอ'],
            ['3. คอลัมน์ "ชื่อเมนูอาหาร" ต้องมีข้อมูล มิฉะนั้นระบบจะข้ามแถวนั้นไป ไม่นำเข้า'],
            ['4. คอลัมน์ "รูปภาพเมนู" (คอลัมน์ K) - วางหรือแทรกรูปภาพลงในเซลล์ของแถวนั้นๆ ได้เลย ระบบจะดึงรูปไปเก็บเป็นรูปเมนูให้อัตโนมัติ (รองรับเฉพาะไฟล์ .xlsx เท่านั้น)'],
            ['5. คอลัมน์ "หมายเหตุ" ไว้สำหรับจดบันทึกในไฟล์ของคุณเอง ระบบยังไม่ได้นำคอลัมน์นี้ไปใช้งาน'],
            ['6. ระบบจะถือว่าเมนูซ้ำกัน เมื่อ ปีงบประมาณ + จังหวัด + อำเภอ + หน่วยงาน + โรงครัว/ร้านอาหาร + ชื่อเมนู ตรงกันทุกอย่าง'],
            ['7. เมื่อพบข้อมูลซ้ำ ระบบจะทำตามตัวเลือกที่เลือกไว้ในหน้าต่างนำเข้า: "ข้ามข้อมูลที่ซ้ำกัน" (จะอัปเดตให้อัตโนมัติถ้าค่าต่างจากเดิม) หรือ "บันทึกแทนข้อมูลที่ซ้ำกัน" (ลบของเดิมแล้วแทนที่ด้วยข้อมูลใหม่)'],
            ['8. เพื่อให้รูปภาพในทุกแถวดูเป็นระเบียบ (ไม่เล็กไม่ใหญ่เกินไป) แนะนำให้ปรับขนาดรูปให้ใกล้เคียงสี่เหลี่ยมจัตุรัส ประมาณ 4x4 ซม. (หรือราว 120x120 พิกเซล) ก่อนแทรก โดยดูกรอบตัวอย่างสีม่วงอ่อนในเซลล์ K2 (แถวตัวอย่าง) เป็นแนวทางขนาด - ลบกรอบตัวอย่างนี้ทิ้งได้ ไม่มีผลต่อการนำเข้าข้อมูล'],
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getColumnDimension('A')->setWidth(110);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $sheet->getStyle('A1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('EEF2FF');
        $sheet->getStyle('A3:A10')->getAlignment()->setWrapText(true);
        for ($row = 3; $row <= 10; $row++) {
            $sheet->getRowDimension($row)->setRowHeight(22);
        }

        return [];
    }
}
