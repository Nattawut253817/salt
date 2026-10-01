<?php

namespace App\Exports\Sheets;

use App\Models\SodiumSurvey;
use App\Models\SurveyYearMapping;
use App\Services\AwarenessPassResolver;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithCustomChunkSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Conditional;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

/**
 * "Export แปลงผล"'s per-respondent sheet for a fiscal year using
 * AwarenessPassSetting::METHOD_QUESTIONS ("วิธีที่ 1: เลือกคำถามเฉพาะ", e.g.
 * FY2568) - one row per respondent: the 2 criteria questions ("เกณฑ์ข้อ 1" =
 * semantic_key is_aware_health, "เกณฑ์ข้อ 2" = semantic_key is_know_limit)
 * and the resulting ผ่าน/ไม่ผ่าน verdict. Deliberately much simpler than
 * AwarenessInterpretationExport's 67-column, 5-section layout (built
 * exclusively for METHOD_SCORE years and their 26-role scoring rubric) -
 * this method has no rubric at all, so there is nothing to gain from
 * forcing this year's data through that export's shape.
 *
 * This is now one sheet of a multi-sheet workbook (see
 * App\Exports\AwarenessInterpretationQuestionsExport) - the 2nd sheet,
 * AwarenessPanelBreakdownSheet, covers every OTHER question this year's
 * form asked (categorized via App\Services\AwarenessPanelBreakdown), which
 * this per-respondent sheet intentionally leaves out to stay focused on
 * just the pass/fail verdict.
 *
 * Only ever constructed for a fiscal year that already passed
 * AwarenessPassResolver::isConfiguredFor() (see AwarenessAssessment
 * Controller::eligibleInterpretationYears()/exportInterpretation()) - both
 * criteria roles have at least one mapped question for this fiscal year.
 */
class AwarenessQuestionsDetailSheet implements FromQuery, WithMapping, WithHeadings, WithTitle, WithStyles, WithCustomChunkSize
{
    const CHUNK_SIZE = 500;

    protected $fiscalYear;

    /** criteriaMappingsFor() results for this fiscal year - loaded once in the constructor, reused for every header/row. */
    protected $awareMappings;
    protected $limitMappings;

    /** AwarenessPassResolver::isPass()'s own scratch cache - reused across every row so this year's mappings are only read once. */
    protected $passCache = [];

    public function __construct($fiscalYear)
    {
        $this->fiscalYear = $fiscalYear;
        $this->awareMappings = SurveyYearMapping::criteriaMappingsFor($fiscalYear, 'is_aware_health');
        $this->limitMappings = SurveyYearMapping::criteriaMappingsFor($fiscalYear, 'is_know_limit');
    }

    public function title(): string
    {
        // Sheet title limit is 31 chars.
        return mb_substr('แปลงผล ' . $this->fiscalYear, 0, 31);
    }

    /**
     * "เกณฑ์ข้อ 1"/"เกณฑ์ข้อ 2" plus, when the admin has tagged one or more
     * real questions to that role, the actual question label(s) so the
     * column header says exactly which question was used - a role can be
     * backed by more than one question at once (see SurveyYearMapping::
     * criteriaMappingsFor()'s own docblock), joined with " / " when so.
     */
    protected function criteriaHeader(string $prefix, $mappings): string
    {
        $labels = $mappings->pluck('question_label')->filter()->unique()->values()->all();

        return $labels ? $prefix . ': ' . implode(' / ', $labels) : $prefix;
    }

    public function headings(): array
    {
        return [
            'รหัสข้อมูล (ID)',
            'วันที่บันทึก',
            'จังหวัด',
            'อำเภอ',
            'ตำบล',
            $this->criteriaHeader('เกณฑ์ข้อ 1', $this->awareMappings),
            $this->criteriaHeader('เกณฑ์ข้อ 2', $this->limitMappings),
            'ผลการประเมิน',
        ];
    }

    public function query(): Builder
    {
        return SodiumSurvey::query()
            ->where('fiscal_year', $this->fiscalYear)
            ->select(['id', 'fiscal_year', 'survey_date', 'province_name', 'district_name', 'sub_district', 'survey_data'])
            ->orderBy('id');
    }

    public function chunkSize(): int
    {
        return self::CHUNK_SIZE;
    }

    /** The respondent's own answer(s) to every question mapped to this role - joined with " / " when more than one question backs it, same order as criteriaHeader() lists their labels. */
    protected function answersFor(SodiumSurvey $survey, $mappings): string
    {
        $data = $survey->survey_data ?? [];
        $values = [];
        foreach ($mappings as $mapping) {
            $values[] = $data[$mapping->question_key] ?? '-';
        }

        return $values ? implode(' / ', $values) : '-';
    }

    /** One respondent (one row from query() above) -> one exported row. */
    public function map($survey): array
    {
        $isPass = AwarenessPassResolver::isPass($survey, $this->passCache);

        return [
            $survey->id,
            $survey->survey_date ? $survey->survey_date->format('d/m/Y') : '-',
            $survey->province_name ?: '-',
            $survey->district_name ?: '-',
            $survey->sub_district ?: '-',
            $this->answersFor($survey, $this->awareMappings),
            $this->answersFor($survey, $this->limitMappings),
            $isPass === null ? '-' : ($isPass ? 'ผ่าน' : 'ไม่ผ่าน'),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $colCount = 8;
        $lastColLetter = Coordinate::stringFromColumnIndex($colCount);
        $lastDataRow = max($sheet->getHighestRow(), 1);

        // --- Row 1: column headers - a single amber band (this method's
        // own color on the "วิธีตั้งเกณฑ์ผ่าน/ไม่ผ่าน" picker, see
        // AwarenessPassSetting::METHODS) instead of the score export's
        // 5-section rainbow, since this sheet has only the one flat layer
        // of columns. ---
        $sheet->getStyle("A1:{$lastColLetter}1")->applyFromArray([
            'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'd97706']],
            'alignment' => ['horizontal' => 'center', 'vertical' => 'center', 'wrapText' => true],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(34);

        if ($lastDataRow > 1) {
            // Same font/valign/wrapText/border look as before, but set as
            // the WORKBOOK's default cell style instead of an explicit
            // applyFromArray() over every data cell - see the identical
            // change (and its full rationale) in AwarenessInterpretationExport
            // ::styles(), the other sheet this same "Export แปลงผล" can
            // produce. A cell that never gets its own explicit style -
            // every data cell here, aside from the couple of columns
            // touched below - falls back to this default automatically, so
            // the visual result is unchanged, just without walking the
            // full range to set it.
            $sheet->getParent()->getDefaultStyle()->applyFromArray([
                'font' => ['size' => 10],
                'alignment' => ['vertical' => 'center', 'wrapText' => true],
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'F1F5F9']],
                ],
            ]);
            $sheet->getStyle("A2:E{$lastDataRow}")->getAlignment()->setHorizontal('center')->setWrapText(false);
            $sheet->getStyle("H2:H{$lastDataRow}")->getAlignment()->setHorizontal('center');

            // "ผลการประเมิน" (last column) - conditional fill so ผ่าน/ไม่ผ่าน
            // reads at a glance without a per-row PHP loop.
            $resultRange = "H2:H{$lastDataRow}";

            $pass = new Conditional();
            $pass->setConditionType(Conditional::CONDITION_CELLIS);
            $pass->setOperatorType(Conditional::OPERATOR_EQUAL);
            $pass->setConditions(['"ผ่าน"']);
            $pass->getStyle()->getFont()->setBold(true)->getColor()->setRGB('047857');
            $pass->getStyle()->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('D1FAE5');

            $fail = new Conditional();
            $fail->setConditionType(Conditional::CONDITION_CELLIS);
            $fail->setOperatorType(Conditional::OPERATOR_EQUAL);
            $fail->setConditions(['"ไม่ผ่าน"']);
            $fail->getStyle()->getFont()->setBold(true)->getColor()->setRGB('BE123C');
            $fail->getStyle()->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFE4E6');

            $sheet->getStyle($resultRange)->setConditionalStyles([$pass, $fail]);
        }

        // --- Column widths: identity columns compact, the two criteria
        // columns wide (they can carry a full question label plus answer,
        // or several joined with " / "), result column compact. ---
        $sheet->getColumnDimension('A')->setWidth(10);
        $sheet->getColumnDimension('B')->setWidth(13);
        foreach (['C', 'D', 'E'] as $letter) {
            $sheet->getColumnDimension($letter)->setWidth(16);
        }
        foreach (['F', 'G'] as $letter) {
            $sheet->getColumnDimension($letter)->setWidth(38);
        }
        $sheet->getColumnDimension('H')->setWidth(13);

        // Keep the header row and the 5 identity columns in view while
        // scrolling through a fiscal year that can run into the thousands
        // of rows.
        $sheet->freezePane('F2');

        return [];
    }
}
