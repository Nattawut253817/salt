<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SaltAssessmentExport implements FromView, ShouldAutoSize, WithStyles, WithTitle
{
    protected $assessment;

    public function __construct($assessment)
    {
        $this->assessment = $assessment;
    }

    public function view(): View
    {
        return view('admin.salt-assessment-excel', [
            'assessment' => $this->assessment
        ]);
    }

    public function title(): string
    {
        return 'SDA0902';
    }

    public function styles(Worksheet $sheet)
    {
        // Styling headers or generic alignments can be done here, 
        // but Since we use FromView, we rely primarily on inline-css from HTML view.
        return [
            // Generic styling if needed
            'A1:Z100' => [
                'alignment' => [
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP,
                    'wrapText' => true,
                ],
            ],
        ];
    }
}
