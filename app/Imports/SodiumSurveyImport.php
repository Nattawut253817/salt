<?php

namespace App\Imports;

use App\Models\DemographicFieldMapping;
use App\Models\SodiumSurvey;
use App\Models\SurveyYearMapping;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;

/**
 * Generic importer for sodium_surveys.
 *
 * Every year's Excel file has the same handful of recognizable demographic
 * columns (hospital_name, hcode, province, ..., congenital_disease) -
 * detected by HEADER TEXT rather than fixed position, since a year's file
 * can add, remove, reorder, or rename columns around them (see FY69's
 * file, which grew the old 10-column demographic block to 17 by splitting
 * out birth date/income/etc.). Whatever isn't one of those recognized
 * columns is that year's own set of survey questions, stored as JSON in
 * survey_data, keyed q1, q2, ... in file order. The question wording for
 * each key is read from the header row and recorded in
 * survey_year_mappings, so a brand new year's form - however many
 * questions it asks, however it words them, however many extra
 * demographic columns it adds - never needs a new migration, model, or
 * import class: just upload it.
 */
class SodiumSurveyImport implements ToCollection, WithCustomCsvSettings, SkipsEmptyRows
{
    // A sanity floor only - not every column this many wide is necessarily
    // valid, but nothing narrower can possibly be a real survey export.
    const MINIMUM_COLUMN_COUNT = 6;

    // The identity columns every year's file has always led with, in the
    // same relative order (only their raw position has ever varied - see
    // FY69, which pushes everything from 'gender' on outward). Matched
    // against the header by exact (trimmed) text first; the 0-based
    // position is used only as a fallback when nothing in the header
    // matches (e.g. a blank/garbled header cell).
    const IDENTITY_FIELD_LABELS = [
        'hospital_name' => ['หน่วยบริการ'],
        'hcode' => ['HCODE'],
        'province_name' => ['จังหวัด'],
        'district_name' => ['อำเภอ'],
        'sub_district' => ['ตำบล'],
        'survey_date' => ['วันที่ทำแบบสอบถาม'],
    ];

    const IDENTITY_FIELD_DEFAULTS = [
        'hospital_name' => 0,
        'hcode' => 1,
        'province_name' => 2,
        'district_name' => 3,
        'sub_district' => 4,
        'survey_date' => 5,
    ];

    // The 4 demographic fields whose column can also be overridden by an
    // admin from "ตั้งค่าแดชบอร์ด" (see DemographicFieldMapping) - resolved
    // the same way as the identity fields above (header text, admin
    // override wins over auto-detection wins over the old fixed default).
    const DEMOGRAPHIC_FIELD_KEYS = ['gender', 'age_range', 'education', 'congenital_disease'];

    // Extra demographic detail columns that FY69's file introduced and no
    // earlier year's file has (see the add_fy69_demographic_detail_columns
    // migration) - matched by header text ONLY, with no positional
    // fallback, since there's no historical position for a column that
    // never existed before. A year whose file doesn't have one of these at
    // all simply leaves it unresolved (and that row's column stays null);
    // a year whose file has it under different wording still keeps that
    // data - it just falls back into survey_data like any other
    // unrecognized column, instead of landing in its own dedicated column.
    const OPTIONAL_FIELD_LABELS = [
        'birth_date' => ['วันเกิด (ด/ว/ป)', 'วันเกิด'],
        'birth_date_unknown' => ['ไม่ทราบวันเกิด'],
        'age_months' => ['อายุ(เดือน)'],
        'income' => ['รายได้'],
        'education_other' => ['ระดับการศึกษาอื่นๆ'],
        'no_congenital_disease' => ['ไม่มีโรคประจำตัว'],
        'congenital_disease_other' => ['โรคประจำตัวอื่นๆ'],
    ];

    // Known legacy (pre-FY69) question wording -> semantic key. Matched
    // against the header row (after trimming) so the two questions the
    // "aware/pass" reports rely on keep resolving automatically after a
    // fresh upload. This intentionally does NOT cover FY69 or later years:
    // their equivalent question(s) either don't exist yet or the criteria
    // for them hasn't been decided (see MainController::awareness()) -
    // tag semantic_key by hand in survey_year_mappings once it has.
    const KNOWN_SEMANTIC_LABELS = [
        'การบริโภคเกลือในปริมาณมากทำให้เกิดปัญหาสุขภาพได้ ใช่หรือไม่' => 'is_aware_health',
        'คนทั่วไปไม่ควรบริโภคโซเดียมเกิน 2,000 มิลลิกรัมต่อวัน ใช่หรือไม่' => 'is_know_limit',
    ];

    protected string $fiscal_year;
    protected string $duplicate_action;
    protected int $importedCount = 0;
    protected int $skippedCount = 0;
    protected int $replacedCount = 0;
    protected int $updatedCount = 0;

    public function __construct($fiscal_year, $duplicate_action = 'skip')
    {
        $this->fiscal_year = (string) $fiscal_year;
        $this->duplicate_action = $duplicate_action;
        \Log::info('==== SODIUM SURVEY IMPORT START (fiscal_year=' . $this->fiscal_year . ', mode=' . $duplicate_action . ') ====');
    }

    public function getCsvSettings(): array
    {
        return ['input_encoding' => 'UTF-8'];
    }

    public function collection(Collection $rows)
    {
        if ($rows->isEmpty()) {
            return;
        }

        $header = $rows->first();
        $dataRows = $rows->slice(1);

        // Validate BEFORE syncMappings() touches the database - syncMappings()
        // always overwrites question_label with whatever text sits in the
        // new file's header, so a mismatched/wrong file uploaded for a
        // fiscal year that's already been configured would otherwise
        // silently corrupt its stored question set.
        $this->assertHeaderMatches($header);

        $synced = $this->syncMappings($header);
        $questionKeys = $synced['questionKeys'];
        $fixedIndices = $synced['fixedIndices'];

        foreach ($dataRows as $row) {
            $this->importRow($row, $questionKeys, $fixedIndices);
        }
    }

    // Above this fraction of a fiscal year's previously-known
    // columns/questions missing from the new file entirely, the file is
    // treated as "not this year's form at all" (wrong file, wrong year
    // selected) rather than "this year's form was revised". A form
    // revision - even a big one, like FY69's - still keeps nearly all of
    // its old question wording somewhere in the new header; it's only a
    // genuinely different survey/year that loses most of it at once.
    const MISMATCH_REJECT_RATIO = 0.5;

    /**
     * Guards against silently corrupting an already-configured fiscal
     * year's question set with an unrelated file - NOT against a
     * legitimate form revision, which resolveFixedColumns()/syncMappings()
     * already handle by header text regardless of where things moved to.
     * this module has no single static template (each year's form can
     * legitimately have its own set of questions, and can legitimately be
     * revised mid-year - see FY69, which grew its demographic block from
     * 10 to 17 columns partway through), so there's no fixed shape to
     * check the file against. Instead:
     *
     *  - the file must have at least MINIMUM_COLUMN_COUNT columns at all
     *    (a sanity floor - anything narrower can't possibly be a real
     *    survey export);
     *  - if this fiscal year has NEVER been uploaded before (no
     *    SurveyYearMapping rows recorded yet), any header is accepted -
     *    that's this module's intentional per-year flexibility, and
     *    there's nothing yet to compare against;
     *  - otherwise, every column/question label already recorded for this
     *    year is looked for ANYWHERE in the new header (not at its old
     *    position - a plain reorder, or new columns inserted before it,
     *    is not a mismatch). Only when MORE THAN MISMATCH_REJECT_RATIO of
     *    those old labels are missing from the new header entirely is the
     *    file rejected - that's what "wrong file / wrong fiscal year
     *    selected" actually looks like, as opposed to a form revision that
     *    renames or drops a handful of columns (the normal cost of a form
     *    being revised, which is fine: those specific old labels simply
     *    stop being recognized and free their question_key for reuse,
     *    while everything else keeps resolving as before).
     */
    protected function assertHeaderMatches(Collection $header): void
    {
        $columnCount = $header->count();
        if ($columnCount < self::MINIMUM_COLUMN_COUNT) {
            throw new \Exception(
                'ไฟล์นี้มีจำนวนคอลัมน์ไม่ครบตามที่กำหนด (ต้องมีอย่างน้อย '
                . self::MINIMUM_COLUMN_COUNT . ' คอลัมน์) กรุณาตรวจสอบไฟล์ที่อัปโหลด'
            );
        }

        $existingLabels = SurveyYearMapping::where('fiscal_year', $this->fiscal_year)
            ->pluck('question_label')
            ->map(fn ($label) => trim((string) $label))
            ->filter(fn ($label) => $label !== '')
            ->unique()
            ->values();

        if ($existingLabels->isEmpty()) {
            // Brand-new fiscal year (or one uploaded before any labels
            // were recorded) - no established header shape to compare
            // against yet, so any header is accepted.
            return;
        }

        $headerLabels = $header
            ->map(fn ($cell) => trim((string) $cell))
            ->filter(fn ($label) => $label !== '')
            ->values();

        $missing = $existingLabels->reject(fn ($label) => $headerLabels->contains($label))->values();
        $missingRatio = $missing->count() / max(1, $existingLabels->count());

        if ($missingRatio > self::MISMATCH_REJECT_RATIO) {
            $foundCount = $existingLabels->count() - $missing->count();
            throw new \Exception(
                'ไฟล์นี้ดูไม่ตรงกับปีงบประมาณ ' . $this->fiscal_year . ' เลย (พบคอลัมน์/คำถามที่เคยมีเพียง '
                . $foundCount . ' จาก ' . $existingLabels->count() . ' รายการในไฟล์นี้ เช่น "'
                . $missing->take(5)->implode('", "') . '") '
                . 'กรุณาตรวจสอบว่าเลือกปีงบประมาณถูกต้อง หรือไฟล์ตรงกับปีที่ต้องการอัปโหลดจริงๆ '
                . '(ถ้าฟอร์มของปีนี้ถูกปรับโครงสร้างใหม่จริง ให้ใช้ปุ่ม "รีเซ็ตรูปแบบคำถาม" ที่หน้าตั้งค่าแดชบอร์ดก่อน)'
            );
        }
    }

    /**
     * Resolves which raw column holds each recognized field in THIS file,
     * by header text - never by raw position:
     *  - the 6 identity columns + the 4 DemographicFieldMapping-
     *    configurable ones, falling back to each field's historical fixed
     *    position only when nothing in the header matches it;
     *  - the OPTIONAL_FIELD_LABELS detail columns (FY69+), which have no
     *    fallback position and are simply omitted when not found.
     * Returns [field_key => column_index]; a field genuinely absent from
     * the file (too narrow, or this year's form just doesn't have it) is
     * omitted from the result.
     */
    protected function resolveFixedColumns(Collection $header): array
    {
        $resolved = [];

        foreach (self::IDENTITY_FIELD_LABELS as $fieldKey => $labels) {
            $index = null;
            foreach ($labels as $label) {
                foreach ($header as $i => $cell) {
                    if (trim((string) $cell) === $label) {
                        $index = (int) $i;
                        break 2;
                    }
                }
            }
            if ($index === null) {
                $index = self::IDENTITY_FIELD_DEFAULTS[$fieldKey];
            }
            if ($index < $header->count()) {
                $resolved[$fieldKey] = $index;
            }
        }

        foreach (self::DEMOGRAPHIC_FIELD_KEYS as $fieldKey) {
            $index = DemographicFieldMapping::columnIndexFor($this->fiscal_year, $fieldKey, $header);
            if ($index < $header->count()) {
                $resolved[$fieldKey] = $index;
            }
        }

        // Best-effort only - no fallback position, no admin override. Not
        // found in this file's header at all just means this year's file
        // doesn't have that column (fine, it stays null on the row) or
        // words it differently (fine, it's preserved in survey_data
        // instead of its own column).
        foreach (self::OPTIONAL_FIELD_LABELS as $fieldKey => $labels) {
            foreach ($labels as $label) {
                $found = false;
                foreach ($header as $i => $cell) {
                    if (trim((string) $cell) === $label) {
                        $resolved[$fieldKey] = (int) $i;
                        $found = true;
                        break;
                    }
                }
                if ($found) {
                    break;
                }
            }
        }

        return $resolved;
    }

    /**
     * Reads every fixed AND dynamic column from the header row.
     *
     * Every field resolveFixedColumns() found (identity + demographic +
     * optional detail columns) is registered as is_fixed_column = true even
     * though they're not stored
     * in survey_data - purely so the "ตั้งค่าแดชบอร์ด" admin screen can show
     * this year's actual header text and let an admin override which
     * column really holds gender/age/education/congenital_disease (see
     * DemographicFieldMapping) if auto-detection ever guesses wrong.
     * EVERY OTHER column - however many there are, wherever they sit -
     * becomes a dynamic survey_data question, in file order, so a year
     * that adds brand new demographic detail columns (birth date, income,
     * ...) never loses that data: it's simply preserved as an extra
     * labeled answer instead of a specially-recognized field.
     *
     * Returns ['questionKeys' => [column_index => question_key], 'fixedIndices' => [field_key => column_index]].
     * An existing semantic_key is never overwritten, only filled in the
     * first time from KNOWN_SEMANTIC_LABELS.
     */
    protected function syncMappings(Collection $header): array
    {
        $columnCount = $header->count();
        $fixedIndices = $this->resolveFixedColumns($header);
        $claimedIndices = array_flip($fixedIndices); // column_index => field_key

        foreach ($fixedIndices as $fieldKey => $index) {
            $label = trim((string) ($header[$index] ?? ''));

            $mapping = SurveyYearMapping::firstOrNew([
                'fiscal_year' => $this->fiscal_year,
                'question_key' => 'fixed_' . $fieldKey,
            ]);
            $mapping->question_label = $label !== '' ? $label : ('คอลัมน์ที่ ' . ($index + 1));
            $mapping->sort_order = $index;
            $mapping->is_fixed_column = true;
            $mapping->column_index = $index;
            $mapping->save();
        }

        $questionKeys = [];
        $order = 0;
        for ($i = 0; $i < $columnCount; $i++) {
            if (isset($claimedIndices[$i])) {
                continue;
            }

            $label = trim((string) ($header[$i] ?? ''));
            if ($label === '') {
                continue;
            }

            $order++;
            $key = 'q' . $order;
            $questionKeys[$i] = $key;

            $mapping = SurveyYearMapping::firstOrNew([
                'fiscal_year' => $this->fiscal_year,
                'question_key' => $key,
            ]);
            $mapping->question_label = $label;
            $mapping->sort_order = $order - 1;
            $mapping->column_index = $i;
            $mapping->is_fixed_column = false;
            if (!$mapping->semantic_key && isset(self::KNOWN_SEMANTIC_LABELS[$label])) {
                $mapping->semantic_key = self::KNOWN_SEMANTIC_LABELS[$label];
            }
            $mapping->save();
        }

        return ['questionKeys' => $questionKeys, 'fixedIndices' => $fixedIndices];
    }

    protected function importRow(Collection $row, array $questionKeys, array $fixedIndices): void
    {
        $hospitalName = trim((string) ($row[$fixedIndices['hospital_name'] ?? 0] ?? ''));
        if ($hospitalName === '') {
            return;
        }

        $hcode = $this->cellToString($row[$fixedIndices['hcode'] ?? 1] ?? null);
        $surveyDate = $this->transformDate($row[$fixedIndices['survey_date'] ?? 5] ?? null);
        $gender = $this->cellToString($row[$fixedIndices['gender'] ?? 6] ?? null);
        $age = $this->cellToString($row[$fixedIndices['age_range'] ?? 7] ?? null);
        $edu = $this->cellToString($row[$fixedIndices['education'] ?? 8] ?? null);
        $congenitalDisease = $this->cellToString($row[$fixedIndices['congenital_disease'] ?? 9] ?? null);

        // Optional FY69+ detail fields - null whenever this year's file
        // doesn't have that column at all (no fallback position exists for
        // them, see OPTIONAL_FIELD_LABELS).
        $optional = [];
        foreach (self::OPTIONAL_FIELD_LABELS as $fieldKey => $labels) {
            $idx = $fixedIndices[$fieldKey] ?? null;
            if ($idx === null) {
                $optional[$fieldKey] = null;
                continue;
            }
            $optional[$fieldKey] = $fieldKey === 'birth_date'
                ? $this->transformDate($row[$idx] ?? null)
                : $this->cellToString($row[$idx] ?? null);
        }

        $surveyData = [];
        foreach ($questionKeys as $colIndex => $key) {
            $surveyData[$key] = $this->cellToString($row[$colIndex] ?? null);
        }

        $newData = array_merge([
            'hospital_name' => $hospitalName,
            'hcode' => $hcode,
            'province_name' => $this->cellToString($row[$fixedIndices['province_name'] ?? 2] ?? null),
            'district_name' => $this->cellToString($row[$fixedIndices['district_name'] ?? 3] ?? null),
            'sub_district' => $this->cellToString($row[$fixedIndices['sub_district'] ?? 4] ?? null),
            'survey_date' => $surveyDate,
            'gender' => $gender,
            'age_range' => $age,
            'education' => $edu,
            'congenital_disease' => $congenitalDisease,
            'survey_data' => $surveyData,
            'update_date' => now(),
        ], $optional);

        // 'replace' mode = "บันทึกลงใหม่เลย" - the admin explicitly chose
        // to skip the duplicate check entirely, so every row must become
        // its own brand-new record. Previously this still ran the lookup
        // below and, when a matching row was found, overwrote it with
        // fill()->save() instead of inserting - which meant a re-upload
        // in "replace" mode silently produced zero new rows, identical to
        // 'skip' mode from the admin's point of view. Insert unconditionally
        // here so the row count actually grows, matching the UI's promise
        // ("บันทึกข้อมูลใหม่ลงไปทันทีโดยไม่มีการตรวจสอบข้อมูลซ้ำ").
        if ($this->duplicate_action === 'replace') {
            SodiumSurvey::create(array_merge(['fiscal_year' => $this->fiscal_year], $newData));
            $this->replacedCount++;
            return;
        }

        $existing = SodiumSurvey::where('fiscal_year', $this->fiscal_year)
            ->where('hcode', $hcode)
            ->where('survey_date', $surveyDate)
            ->where('gender', $gender)
            ->where('age_range', $age)
            ->where('education', $edu)
            ->first();

        if ($existing) {
            // 'skip' mode: only touch the row if something actually
            // changed, so a re-upload of the same file is a true no-op.
            $changed = $existing->hospital_name !== $newData['hospital_name']
                || $existing->province_name !== $newData['province_name']
                || $existing->district_name !== $newData['district_name']
                || $existing->sub_district !== $newData['sub_district']
                || $existing->congenital_disease !== $newData['congenital_disease']
                || $existing->birth_date_unknown !== $newData['birth_date_unknown']
                || $existing->age_months !== $newData['age_months']
                || $existing->income !== $newData['income']
                || $existing->education_other !== $newData['education_other']
                || $existing->no_congenital_disease !== $newData['no_congenital_disease']
                || $existing->congenital_disease_other !== $newData['congenital_disease_other']
                || json_encode($existing->birth_date) !== json_encode($newData['birth_date'])
                || json_encode($existing->survey_data) !== json_encode($newData['survey_data']);

            if ($changed) {
                $existing->fill($newData)->save();
                $this->updatedCount++;
            } else {
                $this->skippedCount++;
            }
            return;
        }

        SodiumSurvey::create(array_merge(['fiscal_year' => $this->fiscal_year], $newData));
        $this->importedCount++;
    }

    /**
     * Normalizes one Excel cell to a plain string (or null), so
     * survey_data holds consistent scalar values regardless of whether
     * PhpSpreadsheet handed back a string, a number, or a DateTime.
     */
    private function cellToString($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        return trim((string) $value);
    }

    private function transformDate($value): ?Carbon
    {
        if (!$value) {
            return null;
        }
        try {
            if ($value instanceof \DateTimeInterface) {
                return Carbon::instance($value);
            }
            if (is_numeric($value)) {
                return Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value));
            }

            return Carbon::parse($value);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getImportedCount(): int
    {
        return $this->importedCount;
    }

    public function getSkippedCount(): int
    {
        return $this->skippedCount;
    }

    public function getReplacedCount(): int
    {
        return $this->replacedCount;
    }

    public function getUpdatedCount(): int
    {
        return $this->updatedCount;
    }
}
