<?php

namespace App\Console\Commands;

use App\Models\SodiumSurvey;
use App\Models\SurveyYearMapping;
use App\Support\DefaultQuestionPanels;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * One-time backfill: copies every row out of the old awareness_assessments
 * (one row per fiscal year before FY69) and awareness_assessments_fy69
 * tables into the new, single sodium_surveys table, and seeds
 * survey_year_mappings with the question labels (and, where the current
 * app already relies on it, semantic keys) those two tables' fixed
 * columns represent.
 *
 * Safe to run more than once: mappings are upserted by (fiscal_year,
 * question_key), and rows are skipped if a sodium_surveys row already
 * exists for the same (fiscal_year, hcode, survey_date, gender, age_range,
 * education) - the same duplicate key the importer uses.
 *
 * php artisan sodium-surveys:migrate-legacy
 * php artisan sodium-surveys:migrate-legacy --dry-run
 */
class MigrateAwarenessToSodiumSurveys extends Command
{
    protected $signature = 'sodium-surveys:migrate-legacy {--dry-run : Report what would happen without writing anything}';

    protected $description = 'Backfill sodium_surveys + survey_year_mappings from the legacy awareness_assessments / awareness_assessments_fy69 tables';

    // Order matches AwarenessAssessment::$fillable's dynamic fields, i.e.
    // Excel columns 11-24 (0-indexed 10-23) of the pre-FY69 questionnaire.
    private const LEGACY_FIELDS = [
        'is_aware_health' => ['label' => 'การบริโภคเกลือในปริมาณมากทำให้เกิดปัญหาสุขภาพได้ ใช่หรือไม่', 'semantic' => 'is_aware_health'],
        'is_know_limit' => ['label' => 'คนทั่วไปไม่ควรบริโภคโซเดียมเกิน 2,000 มิลลิกรัมต่อวัน ใช่หรือไม่', 'semantic' => 'is_know_limit'],
        'freq_instant_food' => ['label' => 'รับประทานอาหารสำเร็จรูป หรือกึ่งสำเร็จรูป', 'semantic' => 'freq_instant_food'],
        'freq_frozen_food' => ['label' => 'อาหารแช่แข็งในร้านสะดวกซื้อ', 'semantic' => 'freq_frozen_food'],
        'freq_pickled_food' => ['label' => 'อาหารหมักดอง หรือ แช่น้ำเกลือ', 'semantic' => 'freq_pickled_food'],
        'freq_home_cooked' => ['label' => 'อาหารปรุงเองที่บ้าน', 'semantic' => 'freq_home_cooked'],
        'freq_outside_food' => ['label' => 'อาหารสั่งหรือซื้อจากนอกบ้าน', 'semantic' => 'freq_outside_food'],
        'add_seasoning_cook' => ['label' => 'มีการเติมเกลือ น้ำปลา ซอสถั่วเหลือง น้ำมันหอย ในระหว่างการปรุงอาหาร', 'semantic' => 'add_seasoning_cook'],
        'add_sauce_table' => ['label' => 'มีการเติมน้ำปลาหรือซีอิ๊วบนโต๊ะอาหารอีกครั้งก่อนรับประทาน', 'semantic' => 'add_sauce_table'],
        'freq_high_sodium' => ['label' => 'มีการบริโภคอาหารประเภทที่มีเกลือในปริมาณสูงมาก', 'semantic' => 'freq_high_sodium'],
        'order_no_msg' => ['label' => 'คุณสั่งอาหารไม่เติมน้ำปลา หรือ ผงชูรส บ่อยแค่ไหน', 'semantic' => 'order_no_msg'],
        'importance_level' => ['label' => 'คุณตระหนักว่าการจำกัดการบริโภคโซเดียมและเกลือ มีความสำคัญมากเท่าใด', 'semantic' => 'importance_level'],
        'effort_level' => ['label' => 'ในแต่ละวันคุณได้พยายามจำกัดหรือลดปริมาณการบริโภคเกลือและโซเดียม', 'semantic' => 'effort_level'],
        'knowledge_level' => ['label' => 'คุณทราบปริมาณโซเดียมหรือเกลือในอาหารที่คุณรับประทานในแต่ละวัน', 'semantic' => 'knowledge_level'],
    ];

    // Order matches AwarenessAssessmentFy69::$fillable's dynamic fields.
    // is_aware_health/is_know_limit are intentionally left with no
    // semantic_key: the app currently treats FY69's "aware/pass" criteria
    // as not-yet-defined everywhere else (admin list, public breakdown
    // chart), so this backfill keeps that instead of silently activating
    // it.
    private const FY69_FIELDS = [
        'add_seasoning_cook' => ['label' => 'ปรุงอาหารทานเองมีการเติมเครื่องปรุงรสเค็ม', 'semantic' => 'add_seasoning_cook'],
        'add_sauce_table' => ['label' => 'ซื้อแกงถุง/อาหารตามสั่ง เติมเครื่องปรุงเพิ่ม', 'semantic' => 'add_sauce_table'],
        'freq_instant_food' => ['label' => 'บะหมี่กึ่งสำเร็จรูป/อาหารสำเร็จรูปแบบกล่อง/อาหารขยะ/ขนมกรุบกรอบ', 'semantic' => 'freq_instant_food'],
        'freq_processed_food' => ['label' => 'อาหารแปรรูป/หมักดอง (เช่น ไส้กรอก แหนม หมูยอ ผักดอง)', 'semantic' => 'freq_processed_food'],
        'is_aware_health' => ['label' => 'ตระหนักว่าทานเค็มมากไป ส่งผลเสียต่อสุขภาพ', 'semantic' => null],
        'is_know_limit' => ['label' => 'ทราบว่าไม่ควรทานเกลือเกิน 1 ช้อนชา/วัน', 'semantic' => null],
        'is_appropriate_intake' => ['label' => 'คิดว่าปริมาณความเค็มที่ทานปัจจุบันอยู่ในระดับใด', 'semantic' => 'is_appropriate_intake'],
        'importance_level' => ['label' => 'ความสำคัญของการลดโซเดียม', 'semantic' => 'importance_level'],
        'effort_level' => ['label' => 'ความพยายามในการลดโซเดียม', 'semantic' => 'effort_level'],
        'knowledge_level' => ['label' => 'ความมั่นใจในการลดโซเดียม', 'semantic' => 'knowledge_level'],
        'behavioral_reduce_salty' => ['label' => 'ลดอาหารที่มีรสเค็มจัด', 'semantic' => 'behavioral_reduce_salty'],
        'behavioral_reduce_processed' => ['label' => 'ลดอาหารแปรรูป/กึ่งสำเร็จรูป', 'semantic' => 'behavioral_reduce_processed'],
        'behavioral_reduce_soup' => ['label' => 'หลีกเลี่ยง/ลดซดน้ำแกง/น้ำซุป', 'semantic' => 'behavioral_reduce_soup'],
        'behavioral_reduce_dipping' => ['label' => 'ลดการจิ้มน้ำจิ้ม', 'semantic' => 'behavioral_reduce_dipping'],
        'behavioral_increase_veg' => ['label' => 'เพิ่มผักผลไม้สด', 'semantic' => 'behavioral_increase_veg'],
        'behavioral_exercise' => ['label' => 'ออกกำลังกายสม่ำเสมอ', 'semantic' => 'behavioral_exercise'],
        'behavioral_drink_water' => ['label' => 'ดื่มน้ำเปล่า 8 แก้ว/วัน', 'semantic' => 'behavioral_drink_water'],
        'behavioral_confidence_change' => ['label' => 'มั่นใจว่าปรับเปลี่ยนพฤติกรรมได้', 'semantic' => 'behavioral_confidence_change'],
        'support_law' => ['label' => 'ประเทศไทยควรมีกฎหมายควบคุมปริมาณโซเดียมในอาหาร', 'semantic' => 'support_law'],
        'support_tax' => ['label' => 'การเก็บภาษีโซเดียม (ผู้ผลิตที่ผลิตอาหารที่มีโซเดียมสูงต้องเสียภาษีเพิ่มขึ้น) จะช่วยให้คนไทยบริโภคโซเดียมน้อยลง', 'semantic' => 'support_tax'],
        'social_adjust_if_relative_sick' => ['label' => 'หากคนในครอบครัว/คนใกล้ชิดป่วยด้วยโรคที่เกิดจากการรับประทานโซเดียมมากเกินไป ท่านจะปรับเปลี่ยนพฤติกรรมตามหรือไม่', 'semantic' => 'social_adjust_if_relative_sick'],
        'social_relative_likes_salty' => ['label' => 'คนในครอบครัว/คนใกล้ชิดชอบรับประทานอาหารรสเค็ม', 'semantic' => 'social_relative_likes_salty'],
        'social_relative_recommends' => ['label' => 'คนในครอบครัว/คนใกล้ชิดแนะนำให้ท่านลดการรับประทานโซเดียม', 'semantic' => 'social_relative_recommends'],
        'heard_media' => ['label' => 'ในช่วง 1 เดือนที่ผ่านมา ท่านเคยได้ยินหรือเห็นสื่อรณรงค์ลดโซเดียม หรือไม่', 'semantic' => 'heard_media'],
        'nearby_restaurants_have_menu' => ['label' => 'ร้านอาหารใกล้บ้าน/ที่ทำงาน มีเมนูลดโซเดียมให้เลือกรับประทาน หรือไม่', 'semantic' => 'nearby_restaurants_have_menu'],
    ];

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $this->migrateTable('awareness_assessments', self::LEGACY_FIELDS, null, $dryRun);
        $this->migrateTable('awareness_assessments_fy69', self::FY69_FIELDS, '2569', $dryRun);

        $this->info($dryRun ? 'Dry run complete - no changes were written.' : 'Done.');

        return self::SUCCESS;
    }

    /**
     * @param string $table Legacy table to read from.
     * @param array $fields ['field_name' => ['label' => ..., 'semantic' => ...|null], ...] in Excel column order.
     * @param string|null $forceFiscalYear Force every migrated row to this fiscal year (FY69's table doesn't
     *                                     always have it reliably set), or null to use the row's own fiscal_year.
     */
    private function migrateTable(string $table, array $fields, ?string $forceFiscalYear, bool $dryRun): void
    {
        if (!Schema::hasTable($table)) {
            $this->warn("Table `{$table}` doesn't exist here - skipping (nothing to migrate).");
            return;
        }

        $total = DB::table($table)->count();
        if ($total === 0) {
            $this->info("`{$table}` has no rows - skipping.");
            // Still seed the mapping so the admin UI has labels ready even
            // before any historical data is migrated. Use the forced
            // fiscal year if there is one, otherwise there's nothing to
            // key the mapping to.
            if ($forceFiscalYear) {
                $this->seedMappings($forceFiscalYear, $fields, $dryRun);
            }
            return;
        }

        $this->info("Migrating {$total} row(s) from `{$table}`...");

        // Seed survey_year_mappings once per distinct fiscal year present.
        $fiscalYears = $forceFiscalYear
            ? collect([$forceFiscalYear])
            : DB::table($table)->distinct()->pluck('fiscal_year')->filter()->values();
        foreach ($fiscalYears as $year) {
            $this->seedMappings((string) $year, $fields, $dryRun);
        }

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $migrated = 0;
        $skipped = 0;

        DB::table($table)->orderBy('id')->chunk(500, function ($rows) use (&$migrated, &$skipped, $fields, $forceFiscalYear, $dryRun, $bar) {
            foreach ($rows as $row) {
                $row = (array) $row;
                $fiscalYear = (string) ($forceFiscalYear ?? ($row['fiscal_year'] ?? ''));

                $surveyData = [];
                $i = 0;
                foreach ($fields as $fieldName => $meta) {
                    $i++;
                    $surveyData['q' . $i] = $row[$fieldName] ?? null;
                }

                $hcode = $row['hcode'] ?? null;
                $surveyDate = $row['survey_date'] ?? null;
                $gender = $row['gender'] ?? null;
                $ageRange = $row['age_range'] ?? null;
                $education = $row['education'] ?? null;

                $alreadyMigrated = SodiumSurvey::where('fiscal_year', $fiscalYear)
                    ->where('hcode', $hcode)
                    ->where('survey_date', $surveyDate)
                    ->where('gender', $gender)
                    ->where('age_range', $ageRange)
                    ->where('education', $education)
                    ->exists();

                if ($alreadyMigrated) {
                    $skipped++;
                    $bar->advance();
                    continue;
                }

                if (!$dryRun) {
                    SodiumSurvey::create([
                        'fiscal_year' => $fiscalYear,
                        'hospital_name' => $row['hospital_name'] ?? null,
                        'hcode' => $hcode,
                        'province_name' => $row['province_name'] ?? null,
                        'district_name' => $row['district_name'] ?? null,
                        'sub_district' => $row['sub_district'] ?? null,
                        'survey_date' => $surveyDate,
                        'gender' => $gender,
                        'age_range' => $ageRange,
                        'education' => $education,
                        'congenital_disease' => $row['congenital_disease'] ?? null,
                        'survey_data' => $surveyData,
                        'update_date' => $row['update_date'] ?? null,
                    ]);
                }
                $migrated++;
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();
        $this->info("`{$table}`: migrated {$migrated}, already present {$skipped}.");
    }

    private function seedMappings(string $fiscalYear, array $fields, bool $dryRun): void
    {
        $i = 0;
        foreach ($fields as $fieldName => $meta) {
            $i++;
            $key = 'q' . $i;

            if ($dryRun) {
                continue;
            }

            $mapping = SurveyYearMapping::firstOrNew([
                'fiscal_year' => $fiscalYear,
                'question_key' => $key,
            ]);
            $mapping->question_label = $meta['label'];
            $mapping->sort_order = $i - 1;
            if (!$mapping->semantic_key && $meta['semantic']) {
                $mapping->semantic_key = $meta['semantic'];
            }
            if (!$mapping->dashboard_panel) {
                $panel = DefaultQuestionPanels::panelFor($meta['label']);
                if ($panel) {
                    $mapping->dashboard_panel = $panel;
                }
            }
            $mapping->save();
        }
    }
}
