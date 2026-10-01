<?php

namespace App\Console\Commands;

use App\Models\SurveyYearMapping;
use App\Support\DefaultQuestionPanels;
use Illuminate\Console\Command;

/**
 * One-time (safe to re-run) backfill: fills in dashboard_panel for any
 * survey_year_mappings row that doesn't have one set yet, by matching its
 * question_label text against the known pre-FY69 / FY69 template wording
 * (see App\Support\DefaultQuestionPanels).
 *
 * Needed because that matching only happens automatically for brand new
 * uploads going forward (SodiumSurveyImport never touched dashboard_panel
 * before it existed) or via sodium-surveys:migrate-legacy - this catches
 * rows that were already imported (including a fresh Excel upload done
 * before this feature shipped) without overwriting anything an admin
 * already chose by hand from "การประเมินความตระหนักรู้ > ตั้งค่าคำถามและเกณฑ์การประเมิน".
 *
 * php artisan sodium-surveys:seed-default-panels
 * php artisan sodium-surveys:seed-default-panels --dry-run
 */
class SeedDefaultDashboardPanels extends Command
{
    protected $signature = 'sodium-surveys:seed-default-panels {--dry-run : Report what would change without writing anything}';

    protected $description = 'Backfill survey_year_mappings.dashboard_panel for existing rows using known question wording';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $rows = SurveyYearMapping::whereNull('dashboard_panel')
            ->where('is_fixed_column', false)
            ->orderBy('fiscal_year')->orderBy('sort_order')->get();
        $updated = 0;

        foreach ($rows as $row) {
            $panel = DefaultQuestionPanels::panelFor($row->question_label ?? '');
            if (!$panel) {
                continue;
            }

            $this->line("fiscal_year={$row->fiscal_year} {$row->question_key} \"{$row->question_label}\" -> panel {$panel}");

            if (!$dryRun) {
                $row->dashboard_panel = $panel;
                $row->save();
            }
            $updated++;
        }

        $this->info(($dryRun ? '[dry-run] would update ' : 'Updated ') . $updated . ' row(s) out of ' . $rows->count() . ' unset.');

        return self::SUCCESS;
    }
}
