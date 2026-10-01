<?php

namespace App\Exports;

use App\Http\Controllers\SurveyQuestionSettingsController;
use App\Models\SodiumSurvey;
use App\Services\AwarenessScoreCalculator;
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
 * "Export แปลงผล" - one row per respondent of a single fiscal year, laid out
 * to match the reference file the admin supplied (awareness_interpretation_
 * round2_2569.xlsx): the same 5-section layout (ข้อมูลพื้นฐาน / ส่วนที่ 2
 * พฤติกรรมการบริโภคโซเดียม / ส่วนที่ 3 Individual belief / ส่วนที่ 3
 * Environmental factor / สรุปผลรวม), each with its own merged 2-row group
 * header (a broad section label over a narrower sub-section label) exactly
 * like rows 3-4 of that reference file, then the real per-column headers on
 * row 5 - so opening this export feels like the same document. Per the
 * admin's own direction, every role also gets its "คะแนนเต็ม" column back:
 * this system stores the respondent's literal Thai answer text rather than
 * the reference file's 1-5 numeric code, so that raw code is resolved on
 * the fly (an admin's own explicit pick first, else the criteria file's own
 * suggestion table - see AwarenessScoreCalculator::rawCodesFor()) and
 * placed immediately before that role's "แปลงคะแนน" column, one pair per
 * role, exactly like the reference file's own paired columns. The one
 * exception is belief_knowledge (free-text "ความรู้" question - it has no
 * discrete raw code to show, see
 * SurveyQuestionSettingsController::ROLE_ANSWER_RAW_SCORES), which keeps
 * only its converted column.
 *
 * Only ever constructed for a fiscal year that already passed
 * AwarenessScoreCalculator::isFullyConfigured() (see AwarenessAssessment
 * Controller::interpretation()/exportInterpretation()) - a year with gaps in
 * its role mapping would just silently score those roles 0, which is fine
 * for the on-screen "pending" dashboard but not something this export
 * should ever hand out as a finished result.
 *
 * Data delivery: FromQuery + WithMapping + WithCustomChunkSize instead of
 * FromCollection. The old FromCollection version pulled the ENTIRE fiscal
 * year into one PHP array first (via a manual chunkById loop that just
 * accumulated every row into $dataRows), then handed that whole array to
 * Maatwebsite\Excel as a single Collection - so for a fiscal year with
 * thousands of respondents, the request's peak memory held that whole
 * array AND a second copy of it (the wrapping Collection) AND everything
 * PhpSpreadsheet needed for the sheet, all at once, before a single cell
 * had even been written. FromQuery lets Maatwebsite\Excel pull the query
 * chunk-by-chunk itself and append each chunk straight to the worksheet
 * (see Sheet::fromQuery()) - only one chunk's worth of rows is ever in
 * PHP memory at a time, and map() is called per row within that chunk.
 * Same DB rows, same column order, same styling - only how they get
 * fetched and hop into the sheet changed.
 */
class AwarenessInterpretationExport implements FromQuery, WithMapping, WithHeadings, WithTitle, WithStyles, WithCustomChunkSize
{
    // Row 1 = title, row 2 = spacer, row 3 = section, row 4 = sub-section,
    // row 5 = actual column headers - data starts row 6. Kept as one
    // constant so every row-number reference below (merges, fills, freeze
    // pane, conditional formatting range) can never drift out of sync.
    const HEADER_ROWS = 5;

    // How many respondents' rows Maatwebsite\Excel pulls from the DB and
    // appends to the sheet at a time (see Sheet::fromQuery()/getChunkSize())
    // - matches the chunk size the old manual chunkById() loop used, kept
    // the same since it was never the bottleneck, just the "load
    // everything into one big array first" step around it was.
    const CHUNK_SIZE = 500;

    protected $fiscalYear;

    /**
     * Built once (see calculator()) and reused for every row map() is
     * called with, rather than once per row - it loads this fiscal year's
     * whole role-to-question mapping from the DB in its constructor, so
     * re-creating it per respondent would mean re-querying that mapping
     * thousands of times over a large fiscal year.
     */
    protected $calculator;

    public function __construct($fiscalYear)
    {
        $this->fiscalYear = $fiscalYear;
    }

    protected function calculator(): AwarenessScoreCalculator
    {
        if (!$this->calculator) {
            $this->calculator = new AwarenessScoreCalculator($this->fiscalYear);
        }
        return $this->calculator;
    }

    public function title(): string
    {
        // Sheet title limit is 31 chars.
        return mb_substr('แปลงผล ' . $this->fiscalYear, 0, 31);
    }

    /**
     * The 26 role columns - most as a "คะแนนเต็ม" (raw pre-conversion code)
     * / "แปลงคะแนน" (converted score) pair, belief_knowledge as just the
     * one converted column (see roleHasRawColumn()) - plus the 8 group/
     * section subtotal columns and the final 3 grand-total columns, in the
     * exact order rowFor() below emits them - kept as one static list so
     * every header row and rowFor() can never drift apart. 67 columns total
     * (A-BO).
     */
    public static function columnLabels(): array
    {
        $labels = ['รหัสข้อมูล (ID)', 'วันที่บันทึก', 'จังหวัด', 'อำเภอ', 'ตำบล'];

        foreach (AwarenessScoreCalculator::ROLES as $roleKey => $meta) {
            if (self::roleHasRawColumn($roleKey)) {
                $labels[] = 'คะแนนเต็ม ' . $meta['title'];
            }
            $labels[] = 'แปลงคะแนน ' . $meta['title'];

            if ($roleKey === 'sec2_4') {
                $labels[] = 'Sum 2.1-2.4 (Total=8)';
            }
            if ($roleKey === 'label_used_3') {
                $labels[] = 'Sum 2.1-2.5 (Total=10)';
            }
            if ($roleKey === 'belief_severity_2') {
                $labels[] = 'Average 3.3-3.4';
            }
            if ($roleKey === 'belief_barriers_3') {
                $labels[] = 'Average 3.6-3.8';
            }
            if ($roleKey === 'belief_selfefficacy') {
                $labels[] = 'Sum individual belief (Total=10)';
            }
            if ($roleKey === 'env_policy_2') {
                $labels[] = 'Average 3.10-3.11';
            }
            if ($roleKey === 'env_social_2') {
                $labels[] = 'Average 3.13-3.14';
            }
            if ($roleKey === 'env_physical') {
                $labels[] = 'Sum environment factor (Total=8)';
            }
        }

        $labels[] = 'Sum all factor (Total=22)';
        $labels[] = 'Sum all (Total=32)';
        $labels[] = 'ผลการประเมิน';

        return $labels;
    }

    /**
     * The 5 broad sections (row 3 of the reference file) - label => [start
     * column, end column] (1-based, inclusive). Matches the reference
     * file's own A3:E3 / F3:W3 / X3:AR3 / AS3:BJ3 / BK3:BL3 merges,
     * re-measured against this export's 67 columns now that every role
     * carries its raw-code column back alongside the converted one.
     */
    public static function sections(): array
    {
        return [
            'ข้อมูลพื้นฐาน' => [1, 5],
            'ส่วนที่ 2 พฤติกรรมการบริโภคโซเดียม' => [6, 27],
            'ส่วนที่ 3 Individual belief' => [28, 47],
            'ส่วนที่ 3 Environmental factor' => [48, 65],
            'สรุปผลรวม' => [66, 67],
        ];
    }

    /** Fill color (for row 3 + row 5) per section, in the same order as sections(). */
    public static function sectionColors(): array
    {
        return ['64748b', '2563eb', 'd97706', '059669', '4f46e5'];
    }

    /** Lighter tint of each section color, used for row 4 (the sub-section row). */
    public static function sectionTints(): array
    {
        return ['e2e8f0', 'bfdbfe', 'fde68a', 'a7f3d0', 'c7d2fe'];
    }

    /**
     * The narrower sub-sections (row 4 of the reference file) - same
     * label => [start, end] shape as sections() above.
     */
    public static function subsections(): array
    {
        return [
            'ข้อมูลพื้นฐาน' => [1, 5],
            'พฤติกรรมการบริโภค (1,2,3,4,5)' => [6, 14],
            'การอ่านฉลากโภชนาการ (0,1)' => [15, 27],
            'ความรู้ (0,2)' => [28, 28],
            'การรับรู้ความเสี่ยง' => [29, 30],
            'การรับรู้ความรุนแรง' => [31, 35],
            'การรับรู้ประโยชน์' => [36, 37],
            'การรับรู้อุปสรรค' => [38, 44],
            'ความมั่นใจในการปรับพฤติกรรม' => [45, 46],
            'ผลรวมความเชื่อส่วนบุคคล' => [47, 47],
            'นโยบาย/กฎหมาย' => [48, 52],
            'แรงกระตุ้นให้ปรับพฤติกรรม' => [53, 54],
            'คนรอบข้าง' => [55, 59],
            'สื่อรณรงค์ (0,2)' => [60, 61],
            'สภาพแวดล้อมทางกายภาพ (0,2)' => [62, 63],
            'ผลรวมปัจจัยสิ่งแวดล้อม' => [64, 64],
            'ผลรวมปัจจัยทั้งหมด' => [65, 65],
            'สรุปผลรวม' => [66, 67],
        ];
    }

    /** Column indexes (1-based) that hold a subtotal/average rather than one question's own raw/converted score - tinted down through the data rows so they stand out from the plain per-question columns. */
    public static function subtotalColumns(): array
    {
        return [14, 27, 35, 44, 47, 52, 59, 64, 65, 66];
    }

    /**
     * Whether $roleKey has a genuine pre-conversion "คะแนนเต็ม" raw code to
     * show - true for every role except belief_knowledge, which is scored
     * from free-text (see AwarenessScoreCalculator::scoreKnowledgeAnswer())
     * and so has no discrete 1-5/0-1 code in
     * SurveyQuestionSettingsController::ROLE_ANSWER_RAW_SCORES at all.
     * Drives both columnLabels() (which column pair to emit) and rowFor()
     * (which value to emit) - kept as one source of truth so the two can
     * never disagree about which roles get a raw column.
     */
    protected static function roleHasRawColumn(string $roleKey): bool
    {
        return array_key_exists($roleKey, SurveyQuestionSettingsController::ROLE_ANSWER_RAW_SCORES);
    }

    /**
     * Sections that have no further split - subsections() repeats their
     * own label back verbatim, over the identical column range (ข้อมูลพื้นฐาน
     * and สรุปผลรวม, both just [start, end] === their section's own range).
     * Rendered as a single header cell spanning rows 3-4 instead of
     * printing the same label twice, one directly under the other.
     */
    protected static function selfContainedSectionLabels(): array
    {
        $subsections = self::subsections();
        $labels = [];
        foreach (self::sections() as $label => $range) {
            if (($subsections[$label] ?? null) === $range) {
                $labels[] = $label;
            }
        }
        return $labels;
    }

    /**
     * The 5 fixed header rows (title, spacer, section band, sub-section
     * band, real column labels) - Maatwebsite\Excel appends these to the
     * sheet first (see Sheet::open()), before query()/map() below stream
     * in the data rows underneath them.
     */
    public function headings(): array
    {
        $labels = self::columnLabels();
        $colCount = count($labels);

        return [
            array_merge(
                ['Export คะแนนความตระหนักรู้ ปีงบประมาณ ' . $this->fiscalYear],
                array_fill(0, $colCount - 1, '')
            ),
            array_fill(0, $colCount, ''), // spacer row
            $this->rowFromRanges(self::sections(), $colCount),
            $this->rowFromRanges(self::subsections(), $colCount, self::selfContainedSectionLabels()),
            $labels,
        ];
    }

    /**
     * The rows to export, as an Eloquent query rather than a pre-loaded
     * collection - Maatwebsite\Excel pulls this CHUNK_SIZE rows at a time
     * (see chunkSize() and Sheet::fromQuery()) and appends each chunk to
     * the sheet immediately via map() below, instead of this export having
     * to load every respondent into one PHP array itself first.
     */
    public function query(): Builder
    {
        return SodiumSurvey::query()
            ->where('fiscal_year', $this->fiscalYear)
            ->select(['id', 'survey_date', 'province_name', 'district_name', 'sub_district', 'survey_data'])
            ->orderBy('id');
    }

    public function chunkSize(): int
    {
        return self::CHUNK_SIZE;
    }

    /** One respondent (one row from query() above) -> one exported row. */
    public function map($survey): array
    {
        // withRawCodes=true - this is the only compute() caller that needs
        // the "คะแนนเต็ม" raw codes, so they're resolved in the SAME pass
        // compute() already makes over ROLES/survey_data rather than a
        // second one per row.
        $score = $this->calculator()->compute($survey, true);
        if (!$score) {
            // Defensive only: this export is only ever built for a fiscal
            // year that already passed AwarenessScoreCalculator::
            // isFullyConfigured() (see this class's own doc-block), so
            // compute() never actually returns null for a real call here -
            // this just keeps a future caller that skips that check from
            // hitting a fatal error, by emitting blank cells for that row
            // instead of silently dropping it (WithMapping has no "skip
            // this row" signal the way the old manual loop's `continue`
            // did).
            return array_fill(0, count(self::columnLabels()), '-');
        }
        return $this->rowFor($survey, $score);
    }

    /** Builds one header row: every cell blank except the first cell of each range, which carries that range's label - the range itself is then merged in styles(). $skipLabels omits a range's label entirely (used for a subsection row whose label would just repeat its section's, already shown once above it - see selfContainedSectionLabels()). */
    protected function rowFromRanges(array $ranges, int $colCount, array $skipLabels = []): array
    {
        $row = array_fill(0, $colCount, '');
        foreach ($ranges as $label => [$start, $end]) {
            if (in_array($label, $skipLabels, true)) {
                continue;
            }
            $row[$start - 1] = $label;
        }
        return $row;
    }

    protected function rowFor(SodiumSurvey $survey, array $score): array
    {
        $roleScores = $score['role_scores'];
        $rawCodes = $score['raw_codes'] ?? [];
        $groupValues = $score['group_values'];

        $row = [
            $survey->id,
            $survey->survey_date ? $survey->survey_date->format('d/m/Y') : '-',
            $survey->province_name ?: '-',
            $survey->district_name ?: '-',
            $survey->sub_district ?: '-',
        ];

        // Sum 2.1-2.4 (Total=8) isn't part of AwarenessScoreCalculator::
        // compute()'s own return value (only the wider "Sum 2.1-2.5" bucket
        // that also folds in the label-awareness roles is) - it's just the
        // 4 behavior-section roles on their own, so it's cheap to total
        // here from the same role_scores compute() already handed back.
        $sum21to24 = ($roleScores['sec2_1'] ?? 0) + ($roleScores['sec2_2'] ?? 0)
            + ($roleScores['sec2_3'] ?? 0) + ($roleScores['sec2_4'] ?? 0);

        foreach (AwarenessScoreCalculator::ROLES as $roleKey => $meta) {
            if (self::roleHasRawColumn($roleKey)) {
                $row[] = $rawCodes[$roleKey] ?? '';
            }
            $row[] = $roleScores[$roleKey] ?? 0;

            if ($roleKey === 'sec2_4') {
                $row[] = round($sum21to24, 2);
            }
            if ($roleKey === 'label_used_3') {
                $row[] = $score['sum_2_1_to_2_5'];
            }
            if ($roleKey === 'belief_severity_2') {
                $row[] = $groupValues['severity'] ?? 0;
            }
            if ($roleKey === 'belief_barriers_3') {
                $row[] = $groupValues['barriers'] ?? 0;
            }
            if ($roleKey === 'belief_selfefficacy') {
                $row[] = $score['sum_individual_belief'];
            }
            if ($roleKey === 'env_policy_2') {
                $row[] = $groupValues['policy'] ?? 0;
            }
            if ($roleKey === 'env_social_2') {
                $row[] = $groupValues['social'] ?? 0;
            }
            if ($roleKey === 'env_physical') {
                $row[] = $score['sum_environment_factor'];
            }
        }

        $row[] = $score['sum_all_factor'];
        $row[] = $score['sum_all'];
        $row[] = $score['result'];

        return $row;
    }

    public function styles(Worksheet $sheet)
    {
        $colCount = count(self::columnLabels());
        $lastColLetter = Coordinate::stringFromColumnIndex($colCount);

        // styles() runs after every data row has already been appended to
        // the sheet (see Sheet::export()/close()), so the sheet's own
        // highest written row is the exact row count to style against -
        // no need to track a row count ourselves the way the old
        // FromCollection version did with $this->dataRowCount.
        $lastDataRow = max($sheet->getHighestRow(), self::HEADER_ROWS);
        $dataRowCount = max(0, $lastDataRow - self::HEADER_ROWS);

        // --- Row 1: title, spanning the full width ---
        $sheet->mergeCells("A1:{$lastColLetter}1");
        $sheet->getStyle("A1:{$lastColLetter}1")->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '3730a3']],
            'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(30);
        $sheet->getRowDimension(2)->setRowHeight(8);

        // --- Rows 3-5: section / sub-section / column-header bands, one
        // color family per section so the "sections" the admin asked for
        // read clearly top to bottom, not just left to right. ---
        $sections = self::sections();
        $sectionColors = self::sectionColors();
        $subsections = self::subsections();
        $selfContained = self::selfContainedSectionLabels();

        $i = 0;
        foreach ($sections as $label => [$start, $end]) {
            $color = $sectionColors[$i] ?? '4f46e5';
            $startLetter = Coordinate::stringFromColumnIndex($start);
            $endLetter = Coordinate::stringFromColumnIndex($end);
            // ข้อมูลพื้นฐาน / สรุปผลรวม have no distinct sub-section label of
            // their own (see selfContainedSectionLabels()) - instead of a
            // row-3 cell and an identically-labeled row-4 cell stacked
            // right under it, merge both rows into one cell so the label
            // only appears once.
            $isSelfContained = in_array($label, $selfContained, true);
            $bottomRow = $isSelfContained ? 4 : 3;

            if ($end > $start || $isSelfContained) {
                $sheet->mergeCells("{$startLetter}3:{$endLetter}{$bottomRow}");
            }
            $sheet->getStyle("{$startLetter}3:{$endLetter}{$bottomRow}")->applyFromArray([
                'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $color]],
                'alignment' => ['horizontal' => 'center', 'vertical' => 'center', 'wrapText' => true],
            ]);

            // Same color family, solid, for that section's slice of the
            // real column-header row (row 5) - ties the 3 header rows of
            // one section together visually. A smaller size than rows 3-4
            // here - every role now carries two header cells ("คะแนนเต็ม X"
            // / "แปลงคะแนน X") instead of one, so this row's text is by far
            // the most crowded of the three.
            $sheet->getStyle("{$startLetter}5:{$endLetter}5")->applyFromArray([
                'font' => ['bold' => true, 'size' => 9, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $color]],
                'alignment' => ['horizontal' => 'center', 'vertical' => 'center', 'wrapText' => true],
            ]);

            $i++;
        }

        $i = 0;
        // subsections() lists more entries than sections() (18 vs 5) - walk
        // it against the same color list by re-deriving which top-level
        // section each sub-range falls inside (its start column falls
        // within that section's own [start,end]).
        $sectionRanges = array_values($sections);
        foreach ($subsections as $label => [$start, $end]) {
            if (in_array($label, $selfContained, true)) {
                continue; // already merged into the row 3-4 section cell above
            }
            $colorIndex = 0;
            foreach ($sectionRanges as $idx => [$secStart, $secEnd]) {
                if ($start >= $secStart && $start <= $secEnd) {
                    $colorIndex = $idx;
                    break;
                }
            }
            $tint = self::sectionTints()[$colorIndex] ?? 'e2e8f0';
            $startLetter = Coordinate::stringFromColumnIndex($start);
            $endLetter = Coordinate::stringFromColumnIndex($end);

            if ($end > $start) {
                $sheet->mergeCells("{$startLetter}4:{$endLetter}4");
            }
            $sheet->getStyle("{$startLetter}4:{$endLetter}4")->applyFromArray([
                'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => '1e293b']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $tint]],
                'alignment' => ['horizontal' => 'center', 'vertical' => 'center', 'wrapText' => true],
            ]);
        }

        $sheet->getRowDimension(3)->setRowHeight(22);
        $sheet->getRowDimension(4)->setRowHeight(22);
        $sheet->getRowDimension(5)->setRowHeight(38);

        // --- Data rows: borders, tabular numeric alignment, and a tint on
        // every subtotal/average column so it stands out from the plain
        // per-question score columns all the way down the sheet. ---
        if ($dataRowCount > 0) {
            // Same font/valign/border/center-alignment look as before, but
            // set as the WORKBOOK's default cell style instead of an
            // explicit applyFromArray()/getAlignment() call over every one
            // of the (67 columns x thousands of rows) data cells.
            // PhpSpreadsheet's getStyle($range) - for ANY property, not
            // just applyFromArray() - resolves through Worksheet::
            // duplicateStyle(), which loops col-by-col, row-by-row and
            // calls getCell() (materializing a real Cell object) for every
            // single cell in the range before it can set that cell's style
            // index. The border/font piece of this was already moved to
            // the default style below in an earlier pass, but the
            // horizontal-center alignment was still being set via a
            // SEPARATE "F6:{lastCol}{lastDataRow}" getStyle() call right
            // after it - for FY2569's ~11,500 respondents x 62 score
            // columns that is ~700,000 getCell()+setXfIndex() calls on its
            // own, on top of another ~115,000 for the subtotal-column loop
            // below (now also removed - see that block's own comment). At
            // roughly a fiscal year's growth rate this was the dominant
            // cost of "Export แปลงผล" for a METHOD_SCORE year, not the
            // scoring math itself (AwarenessScoreCalculator::compute() is a
            // few dozen array operations per row - cheap by comparison).
            // Folding horizontal-center into the one workbook-wide default
            // call below makes it O(1) instead of O(cells): every data
            // cell that never gets its OWN explicit style falls back to
            // this default automatically. The one visible side effect is
            // that the 5 identity columns (A-E: ID/date/province/district/
            // ตำบล) are now center-aligned too instead of left/general -
            // every OTHER row in this sheet (all 5 header rows, and every
            // score column F onward) was already center-aligned, so this
            // just makes the whole sheet consistent rather than a visual
            // regression.
            $sheet->getParent()->getDefaultStyle()->applyFromArray([
                'font' => ['size' => 10],
                'alignment' => ['vertical' => 'center', 'horizontal' => 'center'],
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'F1F5F9']],
                ],
            ]);

            // Subtotal/average columns (bold + light-purple fill) - a
            // CONDITION_EXPRESSION conditional-format rule with an
            // always-true formula instead of applyFromArray() over the
            // literal column range. Conditional::setConditionalStyles()
            // stores ONE rule keyed by the range string itself (see
            // Style::setConditionalStyles() -> Worksheet::
            // setConditionalStyles()) - it does NOT loop over the range's
            // cells the way applyFromArray()/getStyle() setters do (that's
            // exactly why the "ผลการประเมิน" pass/fail fill just below
            // already used this approach and was never part of the
            // slowdown). Excel evaluates TRUE() as unconditionally true
            // for every cell in the range, so the visual result - every
            // data-row cell in a subtotal column bold + tinted - is
            // identical to the old per-cell loop, just defined once instead
            // of walked ~115,000 times over.
            foreach (self::subtotalColumns() as $colIndex) {
                $letter = Coordinate::stringFromColumnIndex($colIndex);
                $subtotalRange = "{$letter}6:{$letter}{$lastDataRow}";

                $alwaysOn = new Conditional();
                $alwaysOn->setConditionType(Conditional::CONDITION_EXPRESSION);
                $alwaysOn->setConditions(['TRUE()']);
                $alwaysOn->getStyle()->getFont()->setBold(true);
                $alwaysOn->getStyle()->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F5F5FF');

                $sheet->getStyle($subtotalRange)->setConditionalStyles([$alwaysOn]);
            }

            // "ผลการประเมิน" (last column) - conditional fill so ผ่าน/ไม่ผ่าน
            // reads at a glance without a per-row PHP loop over up to
            // several thousand rows.
            $resultLetter = Coordinate::stringFromColumnIndex($colCount);
            $resultRange = "{$resultLetter}6:{$resultLetter}{$lastDataRow}";

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

        // --- Column widths: identity columns wider, score columns compact ---
        $sheet->getColumnDimension('A')->setWidth(10);
        $sheet->getColumnDimension('B')->setWidth(13);
        foreach (['C', 'D', 'E'] as $letter) {
            $sheet->getColumnDimension($letter)->setWidth(16);
        }
        for ($c = 6; $c <= $colCount; $c++) {
            $letter = Coordinate::stringFromColumnIndex($c);
            $sheet->getColumnDimension($letter)->setWidth(in_array($c, self::subtotalColumns()) ? 15 : 11);
        }
        $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($colCount))->setWidth(13);

        // Keep the 5 header rows and the 5 identity columns in view while
        // scrolling through a fiscal year that can run into the thousands
        // of rows.
        $sheet->freezePane('F' . (self::HEADER_ROWS + 1));

        return [];
    }
}
