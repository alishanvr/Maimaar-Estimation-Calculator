<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ReportDashboardRequest;
use App\Services\Pdf\PdfSettingsService;
use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(private readonly ReportService $reportService) {}

    /**
     * Return pre-aggregated dashboard data for the reports page.
     */
    public function dashboard(ReportDashboardRequest $request): JsonResponse
    {
        $data = $this->reportService->getDashboardData(
            $request->validated(),
            $request->user()
        );

        return response()->json(['data' => $data]);
    }

    /**
     * Export filtered estimations as CSV download.
     */
    public function exportCsv(ReportDashboardRequest $request): StreamedResponse
    {
        $rows = $this->reportService->getCsvRows(
            $request->validated(),
            $request->user()
        );

        $filename = 'estimations-report-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');

            if (count($rows) > 0) {
                fputcsv($handle, array_keys($rows[0]));
            }

            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * Export a summary report as PDF download.
     */
    public function exportPdf(ReportDashboardRequest $request): Response
    {
        $data = $this->reportService->getDashboardData(
            $request->validated(),
            $request->user()
        );

        $pdfSettings = app(PdfSettingsService::class);

        $pdf = Pdf::loadView('pdf.report-dashboard', [
            'data' => $data,
            'filters' => $request->validated(),
            'generatedAt' => now()->format('d M Y, H:i'),
            'companyName' => $pdfSettings->companyName(),
            'logoPath' => $pdfSettings->logoAbsolutePath(),
            'fontFamilyCss' => $pdfSettings->fontFamilyCss(),
            'headerColor' => $pdfSettings->headerColor(),
            'showPageNumbers' => $pdfSettings->showPageNumbers(),
            'footerText' => $pdfSettings->footerText(),
        ])->setPaper($pdfSettings->paperSize(), 'portrait');

        $filename = 'report-dashboard-'.now()->format('Y-m-d').'.pdf';

        return $pdf->download($filename);
    }
}
