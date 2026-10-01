<?php

namespace App\Exports;

use App\Exports\Sheets\AwarenessQuestionsDetailSheet;
use App\Exports\Sheets\AwarenessPanelBreakdownSheet;
use App\Services\AwarenessPanelBreakdown;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * "Export แปลงผล" for a fiscal year using AwarenessPassSetting::
 * METHOD_QUESTIONS ("วิธีที่ 1: เลือกคำถามเฉพาะ", e.g. FY2568) - a 2-sheet
 * workbook: AwarenessQuestionsDetailSheet (one row per respondent - the 2
 * criteria questions and the ผ่าน/ไม่ผ่าน verdict) plus, when this fiscal
 * year has any SurveyYearMapping::dashboard_panel-tagged questions,
 * AwarenessPanelBreakdownSheet ("สรุปตามหมวด" - every OTHER question the
 * form asked, categorized and summarized - see App\Services\
 * AwarenessPanelBreakdown). Deliberately much simpler than
 * AwarenessInterpretationExport (the 67-column, 5-section layout built
 * exclusively for METHOD_SCORE years and their 26-role scoring rubric) -
 * this method has no rubric at all, so there is nothing to gain from
 * forcing this year's data through that export's shape.
 *
 * Only ever constructed for a fiscal year that already passed
 * AwarenessPassResolver::isConfiguredFor() (see AwarenessAssessment
 * Controller::eligibleInterpretationYears()/exportInterpretation()).
 */
class AwarenessInterpretationQuestionsExport implements WithMultipleSheets
{
    protected $fiscalYear;

    public function __construct($fiscalYear)
    {
        $this->fiscalYear = $fiscalYear;
    }

    public function sheets(): array
    {
        $sheets = [new AwarenessQuestionsDetailSheet($this->fiscalYear)];

        $panelBreakdown = AwarenessPanelBreakdown::forYear($this->fiscalYear);
        if (!empty($panelBreakdown)) {
            $sheets[] = new AwarenessPanelBreakdownSheet($panelBreakdown, $this->fiscalYear);
        }

        return $sheets;
    }
}
