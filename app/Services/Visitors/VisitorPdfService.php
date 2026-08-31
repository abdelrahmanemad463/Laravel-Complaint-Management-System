<?php

namespace App\Services\Visitors;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

/**
 * Generates a professional, email/WhatsApp-ready PDF of an inspection report.
 *
 * It consumes the exact same data as the web report (VisitorReportService) so
 * there is a single report implementation; only the rendering differs.
 */
class VisitorPdfService
{
    public function __construct(
        private VisitorReportService $reports,
    ) {}

    /**
     * @return \Barryvdh\DomPDF\PDF
     */
    public function render(\App\Models\VisitorVisit $visit)
    {
        $report = $this->reports->build($visit);

        // Resolve private photos to absolute paths so DomPDF can embed them.
        $photos = [];
        foreach ($report['violations'] as $item) {
            foreach ($item->photos as $photo) {
                $path = Storage::disk('local')->path($photo->path);
                if (is_file($path)) {
                    $photos[$photo->id] = $path;
                }
            }
        }

        $company = __('visitors.report_company_name');

        return Pdf::loadView('visitors.reports.print', [
            'report' => $report,
            'photos' => $photos,
            'company' => $company,
            'generatedAt' => now(),
        ]);
    }

    public function stream(\App\Models\VisitorVisit $visit, string $filename): mixed
    {
        return $this->render($visit)->stream($filename);
    }

    public function download(\App\Models\VisitorVisit $visit, string $filename): mixed
    {
        return $this->render($visit)->download($filename);
    }
}
