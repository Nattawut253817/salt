<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Which "พฤติกรรมการบริโภคโซเดียม" home-dashboard behavior role(s) this
     * question has been tagged as evidence for, and which of that
     * question's own answers count as "ผ่าน" for EACH of those roles - a
     * JSON OBJECT keyed by role name, e.g.
     * {"add_seasoning_cook": ["ไม่เคยเลย"], "freq_instant_food": null}
     * (null value = use that role's built-in default from
     * SurveyQuestionSettingsController::BEHAVIOR_META instead of a
     * specific pick). Deliberately a SEPARATE column from semantic_key
     * (rather than reusing it), and deliberately NOT sharing semantic_key's
     * criteria_pass_values column even for a question that also has a
     * semantic_key - each role can need a totally different set of "pass"
     * answers on the very same question, so pass-values have to be stored
     * per role, not per row. semantic_key is a single exclusive slot - one
     * question can hold only one semantic_key at a time - while a question
     * is allowed to serve more than one behavior role at once (and still
     * keep an unrelated semantic_key, e.g. is_aware_health, at the same
     * time), and a behavior role is allowed to be backed by more than one
     * question at once. Null/empty means "not tagged for any behavior
     * role".
     *
     * Set from "การประเมินความตระหนักรู้ > ตั้งค่าเกณฑ์ความตระหนักรู้ > คอลัมน์
     * สำหรับการ์ด 'พฤติกรรมการบริโภคโซเดียม'" - see
     * App\Http\Controllers\SurveyQuestionSettingsController::updateBehavior()
     * and App\Models\SurveyYearMapping::behaviorMappingsFor().
     */
    public function up(): void
    {
        Schema::table('survey_year_mappings', function (Blueprint $table) {
            $table->json('behavior_roles')->nullable()->after('criteria_pass_values');
        });

        // Move any question already tagged - via the old single-slot
        // semantic_key + criteria_pass_values columns - as one of the
        // home-dashboard behavior roles over to this new, non-exclusive
        // behavior_roles column, so every "พฤติกรรมการบริโภคโซเดียม" card
        // assignment (including any custom pass-values an admin already
        // picked) made before this migration keeps working exactly as
        // configured. semantic_key and criteria_pass_values are cleared
        // for those rows afterward: a behavior role no longer occupies
        // either column at all going forward, which is what frees that
        // same question up to also be picked as this year's
        // is_aware_health / is_know_limit criteria question without
        // "moving away" from its behavior-card use (a row that already had
        // a behavior-role semantic_key could never have also had a
        // criteria-role semantic_key, since that was a single column, so
        // this move never overwrites real criteria data).
        $behaviorRoleNames = [
            'add_seasoning_cook', 'freq_instant_food', 'freq_pickled_food',
            'importance_level', 'freq_processed_food', 'behavioral_reduce_dipping',
            // No longer offered in the settings UI, but migrated too in
            // case any old data still references them.
            'behavioral_reduce_soup', 'behavioral_reduce_salty',
            'freq_high_sodium', 'effort_level',
        ];

        \DB::table('survey_year_mappings')
            ->whereIn('semantic_key', $behaviorRoleNames)
            ->orderBy('id')
            ->get(['id', 'semantic_key', 'criteria_pass_values'])
            ->each(function ($row) {
                $passValues = $row->criteria_pass_values ? json_decode($row->criteria_pass_values, true) : null;
                \DB::table('survey_year_mappings')
                    ->where('id', $row->id)
                    ->update([
                        'behavior_roles' => json_encode([$row->semantic_key => $passValues]),
                        'semantic_key' => null,
                        'criteria_pass_values' => null,
                    ]);
            });
    }

    public function down(): void
    {
        // Best-effort restore: a role tagged on more than one question, or
        // a question tagged with more than one role, can't be perfectly
        // reversed into the old single-slot columns - this restores the
        // first role found on each row (with that role's own pass-values,
        // if any), matching the pre-migration shape for the common case of
        // one question per role.
        \DB::table('survey_year_mappings')
            ->whereNotNull('behavior_roles')
            ->orderBy('id')
            ->get(['id', 'behavior_roles'])
            ->each(function ($row) {
                $roles = json_decode((string) $row->behavior_roles, true);
                if (!empty($roles)) {
                    $firstRole = array_key_first($roles);
                    \DB::table('survey_year_mappings')
                        ->where('id', $row->id)
                        ->update([
                            'semantic_key' => $firstRole,
                            'criteria_pass_values' => $roles[$firstRole] !== null ? json_encode($roles[$firstRole]) : null,
                        ]);
                }
            });

        Schema::table('survey_year_mappings', function (Blueprint $table) {
            $table->dropColumn('behavior_roles');
        });
    }
};
