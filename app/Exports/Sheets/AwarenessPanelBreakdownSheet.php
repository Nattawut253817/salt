<?php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Illuminate\Support\Collection;

/**
 * "สรุปตามหมวด" - an additional sheet on "Export แปลงผล" for a fiscal year
 * that has any SurveyYearMapping::dashboard_panel-tagged questions (the
 * same 4 behavior/attitude panels the public /awareness dashboard charts -
 * see App\Services\AwarenessPanelBreakdown, which builds the array this
 * sheet just flattens into rows): every OTHER question the form asked,
 * beyond whichever questions this year's own pass/fail method already
 * covers - one row per answer value per question, grouped by panel, so an
 * admin gets the full "who answered what" picture in the same workbook as
 * the per-respondent sheet, not just the pass/fail verdict.
 */
class AwarenessPanelBreakdownSheet implements FromCollection, WithTitle, WithHeadings, WithStyles, ShouldAutoSize
{
    protected $panelBreakdown;
    protected $fiscalYear;

    public function __construct(array $panelBreakdown, $fiscalYear)
    {
        $this->panelBreakdown = $panelBreakdown;
        $this->fiscalYear = $fiscalYear;
    }

    public function title(): string
    {
        return 'สรุปตามหมวด';
    }

    public function headings(): array
    {
        return [
            ['สรุปคำถามอื่นๆ แยกตามหมวด - ปีงบประมาณ ' . $this->fiscalYear],
            [''],
            ['หมวด', 'คำถาม', 'คำตอบ', 'จำนวน (คน)', 'ร้อยละ (%)'],
        ];
    }

    public function collection()
    {
        $rows = [];
        foreach ($this->panelBreakdown as $panel) {
            foreach ($panel['questions'] as $question) {
                foreach ($question['distribution'] as $seg) {
                    $rows[] = [
                        $panel['title'],
                        $question['label'],
                        $seg['value'],
                        $seg['count'],
                        $seg['pct'],
                    ];
                }
            }
        }

        return new Collection($rows);
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = max($sheet->getHighestRow(), 3);

        $sheet->mergeCells('A1:E1');
        $sheet->getStyle('A1:E1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 13, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4f46e5']],
            'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(28);

        $sheet->getStyle('A3:E3')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => '1e293b']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'e2e8f0']],
            'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'cbd5e1']],
            ],
        ]);

        if ($lastRow > 3) {
            // Full grid + vertical-centering first, on every data cell -
            // makes the merged หมวด/คำถาม blocks below read as one clean
            // bordered row-group instead of plain repeated text (matches
            // the printed-report look asked for, in place of the earlier
            // flat "same label on every row" table).
            $sheet->getStyle("A4:E{$lastRow}")->applyFromArray([
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'e2e8f0']],
                ],
                'alignment' => ['vertical' => 'center', 'wrapText' => true],
            ]);
            $sheet->getStyle("D4:E{$lastRow}")->getAlignment()->setHorizontal('center');
            $sheet->getStyle("A4:A{$lastRow}")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => '312e81']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'eef2ff']],
            ]);
            $sheet->getStyle("B4:B{$lastRow}")->applyFromArray([
                'font' => ['color' => ['rgb' => '334155']],
            ]);

            // Merge each หมวด's cell down across every row its questions
            // occupy, and each คำถาม's cell down across its own answer
            // rows, so every group label prints exactly once (row math
            // mirrors collection()'s own nested loop exactly, so the
            // ranges always land on the right rows).
            $row = 4;
            foreach ($this->panelBreakdown as $panel) {
                $panelStartRow = $row;
                foreach ($panel['questions'] as $question) {
                    $distCount = count($question['distribution']);
                    if ($distCount === 0) {
                        continue;
                    }
                    $questionStartRow = $row;
                    $row += $distCount;
                    $questionEndRow = $row - 1;
                    if ($questionEndRow > $questionStartRow) {
                        $sheet->mergeCells("B{$questionStartRow}:B{$questionEndRow}");
                    }
                }
                $panelEndRow = $row - 1;
                if ($panelEndRow > $panelStartRow) {
                    $sheet->mergeCells("A{$panelStartRow}:A{$panelEndRow}");
                }
            }
        }

        $sheet->freezePane('A4');

        return [];
    }
}
