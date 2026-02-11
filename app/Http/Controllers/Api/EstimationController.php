<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\BulkExportRequest;
use App\Http\Requests\Api\CalculateEstimationRequest;
use App\Http\Requests\Api\StoreEstimationRequest;
use App\Http\Requests\Api\UpdateEstimationRequest;
use App\Http\Resources\Api\EstimationCollection;
use App\Http\Resources\Api\EstimationResource;
use App\Models\Estimation;
use App\Services\Estimation\EstimationService;
use App\Services\Pdf\PdfSettingsService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class EstimationController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private readonly EstimationService $estimationService) {}

    /**
     * Display a paginated listing of estimations.
     */
    public function index(Request $request): EstimationCollection
    {
        $this->authorize('viewAny', Estimation::class);

        $query = $request->user()->isAdmin()
            ? Estimation::query()
            : $request->user()->estimations();

        if ($request->has('status')) {
            $query->where('status', $request->query('status'));
        }

        $estimations = $query->latest()->paginate(15);

        return new EstimationCollection($estimations);
    }

    /**
     * Store a newly created estimation.
     */
    public function store(StoreEstimationRequest $request): JsonResponse
    {
        $this->authorize('create', Estimation::class);

        $estimation = $request->user()->estimations()->create([
            ...$request->safe()->except('input_data'),
            'input_data' => $request->validated('input_data', []),
            'status' => 'draft',
        ]);

        activity()
            ->causedBy($request->user())
            ->performedOn($estimation)
            ->log('created estimation');

        return (new EstimationResource($estimation))
            ->additional(['message' => 'Estimation created successfully.'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified estimation.
     */
    public function show(Estimation $estimation): EstimationResource
    {
        $this->authorize('view', $estimation);

        $estimation->load('items');

        return new EstimationResource($estimation);
    }

    /**
     * Update the specified estimation.
     */
    public function update(UpdateEstimationRequest $request, Estimation $estimation): EstimationResource
    {
        $this->authorize('update', $estimation);

        $data = $request->safe()->except('input_data');

        if ($request->has('input_data')) {
            $data['input_data'] = $request->validated('input_data');
            $data['status'] = 'draft';
            $data['results_data'] = null;
            $data['total_weight_mt'] = null;
            $data['total_price_aed'] = null;
        }

        $estimation->update($data);

        activity()
            ->causedBy($request->user())
            ->performedOn($estimation)
            ->log('updated estimation');

        return new EstimationResource($estimation->fresh());
    }

    /**
     * Soft delete the specified estimation.
     */
    public function destroy(Request $request, Estimation $estimation): JsonResponse
    {
        $this->authorize('delete', $estimation);

        activity()
            ->causedBy($request->user())
            ->performedOn($estimation)
            ->log('deleted estimation');

        $estimation->delete();

        return response()->json([
            'message' => 'Estimation deleted successfully.',
        ]);
    }

    /**
     * Trigger server-side calculation for an estimation.
     */
    public function calculate(CalculateEstimationRequest $request, Estimation $estimation): EstimationResource|JsonResponse
    {
        $this->authorize('calculate', $estimation);

        if (empty($estimation->input_data)) {
            return response()->json([
                'message' => 'Estimation has no input data to calculate.',
            ], 422);
        }

        $markups = $request->validated('markups', []);

        try {
            $estimation = $this->estimationService->calculateAndSave($estimation, $markups);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Calculation failed: '.$e->getMessage(),
            ], 422);
        }

        activity()
            ->causedBy($request->user())
            ->performedOn($estimation)
            ->log('calculated estimation');

        return new EstimationResource($estimation);
    }

    /**
     * Mark a calculated estimation as finalized (read-only).
     */
    public function finalize(Request $request, Estimation $estimation): EstimationResource|JsonResponse
    {
        $this->authorize('update', $estimation);

        if (! $estimation->isCalculated()) {
            return response()->json([
                'message' => 'Only calculated estimations can be finalized.',
            ], 422);
        }

        $estimation->update(['status' => 'finalized']);

        activity()
            ->causedBy($request->user())
            ->performedOn($estimation)
            ->log('finalized estimation');

        return new EstimationResource($estimation->fresh());
    }

    /**
     * Unlock a finalized estimation back to draft.
     */
    public function unlock(Request $request, Estimation $estimation): EstimationResource|JsonResponse
    {
        $this->authorize('update', $estimation);

        if (! $estimation->isFinalized()) {
            return response()->json([
                'message' => 'Only finalized estimations can be unlocked.',
            ], 422);
        }

        $estimation->update([
            'status' => 'draft',
            'results_data' => null,
            'total_weight_mt' => null,
            'total_price_aed' => null,
        ]);

        activity()
            ->causedBy($request->user())
            ->performedOn($estimation)
            ->log('unlocked estimation');

        return new EstimationResource($estimation->fresh());
    }

    /**
     * Get Detail sheet data.
     */
    public function detail(Estimation $estimation): JsonResponse
    {
        return $this->getSheetData($estimation, 'detail');
    }

    /**
     * Get Recap (summary) sheet data.
     */
    public function recap(Estimation $estimation): JsonResponse
    {
        return $this->getSheetData($estimation, 'summary');
    }

    /**
     * Get FCPBS sheet data.
     */
    public function fcpbs(Estimation $estimation): JsonResponse
    {
        return $this->getSheetData($estimation, 'fcpbs');
    }

    /**
     * Get SAL sheet data.
     */
    public function sal(Estimation $estimation): JsonResponse
    {
        return $this->getSheetData($estimation, 'sal');
    }

    /**
     * Get BOQ sheet data.
     */
    public function boq(Estimation $estimation): JsonResponse
    {
        return $this->getSheetData($estimation, 'boq');
    }

    /**
     * Get JAF sheet data.
     */
    public function jaf(Estimation $estimation): JsonResponse
    {
        return $this->getSheetData($estimation, 'jaf');
    }

    /**
     * Export BOQ as PDF.
     */
    public function exportBoq(Request $request, Estimation $estimation): JsonResponse|Response
    {
        $this->authorize('view', $estimation);

        if (! $estimation->isCalculated() && $estimation->status !== 'finalized') {
            return response()->json([
                'message' => 'Estimation has not been calculated yet.',
            ], 422);
        }

        $boqData = $estimation->results_data['boq'] ?? null;

        if (! $boqData) {
            return response()->json([
                'message' => 'BOQ data is not available.',
            ], 422);
        }

        activity()
            ->causedBy($request->user())
            ->performedOn($estimation)
            ->log('exported BOQ PDF');

        $pdfSettings = app(PdfSettingsService::class);
        $pdf = Pdf::loadView('pdf.boq', $this->getPdfViewData($estimation, 'boqData', $boqData))
            ->setPaper($pdfSettings->paperSize(), 'landscape');

        $filename = 'BOQ-'.($estimation->quote_number ?? 'export').'.pdf';

        return $pdf->download($filename);
    }

    /**
     * Export JAF as PDF.
     */
    public function exportJaf(Request $request, Estimation $estimation): JsonResponse|Response
    {
        $this->authorize('view', $estimation);

        if (! $estimation->isCalculated() && $estimation->status !== 'finalized') {
            return response()->json([
                'message' => 'Estimation has not been calculated yet.',
            ], 422);
        }

        $jafData = $estimation->results_data['jaf'] ?? null;

        if (! $jafData) {
            return response()->json([
                'message' => 'JAF data is not available.',
            ], 422);
        }

        activity()
            ->causedBy($request->user())
            ->performedOn($estimation)
            ->log('exported JAF PDF');

        $pdfSettings = app(PdfSettingsService::class);
        $pdf = Pdf::loadView('pdf.jaf', $this->getPdfViewData($estimation, 'jafData', $jafData))
            ->setPaper($pdfSettings->paperSize(), 'portrait');

        $filename = 'JAF-'.($estimation->quote_number ?? 'export').'.pdf';

        return $pdf->download($filename);
    }

    /**
     * Export Recap as PDF.
     */
    public function exportRecap(Request $request, Estimation $estimation): JsonResponse|Response
    {
        $this->authorize('view', $estimation);

        if (! $estimation->isCalculated() && $estimation->status !== 'finalized') {
            return response()->json([
                'message' => 'Estimation has not been calculated yet.',
            ], 422);
        }

        $recapData = $estimation->results_data['summary'] ?? null;

        if (! $recapData) {
            return response()->json([
                'message' => 'Recap data is not available.',
            ], 422);
        }

        activity()
            ->causedBy($request->user())
            ->performedOn($estimation)
            ->log('exported Recap PDF');

        $pdfSettings = app(PdfSettingsService::class);
        $pdf = Pdf::loadView('pdf.recap', $this->getPdfViewData($estimation, 'recapData', $recapData))
            ->setPaper($pdfSettings->paperSize(), 'portrait');

        $filename = 'Recap-'.($estimation->quote_number ?? 'export').'.pdf';

        return $pdf->download($filename);
    }

    /**
     * Export Detail as PDF.
     */
    public function exportDetail(Request $request, Estimation $estimation): JsonResponse|Response
    {
        $this->authorize('view', $estimation);

        if (! $estimation->isCalculated() && $estimation->status !== 'finalized') {
            return response()->json([
                'message' => 'Estimation has not been calculated yet.',
            ], 422);
        }

        $detailData = $estimation->results_data['detail'] ?? null;

        if (! $detailData) {
            return response()->json([
                'message' => 'Detail data is not available.',
            ], 422);
        }

        activity()
            ->causedBy($request->user())
            ->performedOn($estimation)
            ->log('exported Detail PDF');

        $pdfSettings = app(PdfSettingsService::class);
        $pdf = Pdf::loadView('pdf.detail', $this->getPdfViewData($estimation, 'detailData', $detailData))
            ->setPaper($pdfSettings->paperSize(), 'landscape');

        $filename = 'Detail-'.($estimation->quote_number ?? 'export').'.pdf';

        return $pdf->download($filename);
    }

    /**
     * Export FCPBS as PDF.
     */
    public function exportFcpbs(Request $request, Estimation $estimation): JsonResponse|Response
    {
        $this->authorize('view', $estimation);

        if (! $estimation->isCalculated() && $estimation->status !== 'finalized') {
            return response()->json([
                'message' => 'Estimation has not been calculated yet.',
            ], 422);
        }

        $fcpbsData = $estimation->results_data['fcpbs'] ?? null;

        if (! $fcpbsData) {
            return response()->json([
                'message' => 'FCPBS data is not available.',
            ], 422);
        }

        activity()
            ->causedBy($request->user())
            ->performedOn($estimation)
            ->log('exported FCPBS PDF');

        $pdfSettings = app(PdfSettingsService::class);
        $pdf = Pdf::loadView('pdf.fcpbs', $this->getPdfViewData($estimation, 'fcpbsData', $fcpbsData))
            ->setPaper($pdfSettings->paperSize(), 'landscape');

        $filename = 'FCPBS-'.($estimation->quote_number ?? 'export').'.pdf';

        return $pdf->download($filename);
    }

    /**
     * Export SAL as PDF.
     */
    public function exportSal(Request $request, Estimation $estimation): JsonResponse|Response
    {
        $this->authorize('view', $estimation);

        if (! $estimation->isCalculated() && $estimation->status !== 'finalized') {
            return response()->json([
                'message' => 'Estimation has not been calculated yet.',
            ], 422);
        }

        $salData = $estimation->results_data['sal'] ?? null;

        if (! $salData) {
            return response()->json([
                'message' => 'SAL data is not available.',
            ], 422);
        }

        activity()
            ->causedBy($request->user())
            ->performedOn($estimation)
            ->log('exported SAL PDF');

        $pdfSettings = app(PdfSettingsService::class);
        $pdf = Pdf::loadView('pdf.sal', $this->getPdfViewData($estimation, 'salData', $salData))
            ->setPaper($pdfSettings->paperSize(), 'landscape');

        $filename = 'SAL-'.($estimation->quote_number ?? 'export').'.pdf';

        return $pdf->download($filename);
    }

    /**
     * Clone an estimation (copy input_data, reset to draft).
     */
    public function clone(Request $request, Estimation $estimation): JsonResponse
    {
        $this->authorize('clone', $estimation);

        $clone = $request->user()->estimations()->create([
            'quote_number' => $estimation->quote_number,
            'revision_no' => $estimation->revision_no,
            'building_name' => $estimation->building_name,
            'building_no' => $estimation->building_no,
            'project_name' => $estimation->project_name,
            'customer_name' => $estimation->customer_name,
            'salesperson_code' => $estimation->salesperson_code,
            'estimation_date' => now(),
            'status' => 'draft',
            'input_data' => $estimation->input_data,
            'results_data' => null,
            'total_weight_mt' => null,
            'total_price_aed' => null,
        ]);

        activity()
            ->causedBy($request->user())
            ->performedOn($clone)
            ->withProperties(['cloned_from' => $estimation->id])
            ->log('cloned estimation');

        return (new EstimationResource($clone))
            ->additional(['message' => 'Estimation cloned successfully.'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Create a revision from an existing estimation.
     */
    public function createRevision(Request $request, Estimation $estimation): JsonResponse
    {
        $this->authorize('createRevision', $estimation);

        $currentRevNo = (int) preg_replace('/\D/', '', $estimation->revision_no ?? '0');
        $nextRevNo = 'R'.str_pad($currentRevNo + 1, 2, '0', STR_PAD_LEFT);

        $revision = $request->user()->estimations()->create([
            'parent_id' => $estimation->id,
            'quote_number' => $estimation->quote_number,
            'revision_no' => $nextRevNo,
            'building_name' => $estimation->building_name,
            'building_no' => $estimation->building_no,
            'project_name' => $estimation->project_name,
            'customer_name' => $estimation->customer_name,
            'salesperson_code' => $estimation->salesperson_code,
            'estimation_date' => now(),
            'status' => 'draft',
            'input_data' => $estimation->input_data,
            'results_data' => null,
            'total_weight_mt' => null,
            'total_price_aed' => null,
        ]);

        activity()
            ->causedBy($request->user())
            ->performedOn($revision)
            ->withProperties(['revision_from' => $estimation->id])
            ->log('created revision');

        return (new EstimationResource($revision))
            ->additional(['message' => 'Revision created successfully.'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Get revision chain for an estimation.
     */
    public function revisions(Estimation $estimation): JsonResponse
    {
        $this->authorize('view', $estimation);

        $chain = $estimation->getRevisionChain();

        return response()->json([
            'data' => $chain->map(fn (Estimation $e) => [
                'id' => $e->id,
                'quote_number' => $e->quote_number,
                'revision_no' => $e->revision_no,
                'status' => $e->status,
                'parent_id' => $e->parent_id,
                'total_weight_mt' => $e->total_weight_mt,
                'total_price_aed' => $e->total_price_aed,
                'created_at' => $e->created_at?->toISOString(),
                'is_current' => $e->id === $estimation->id,
            ]),
        ]);
    }

    /**
     * Compare two estimations side-by-side.
     */
    public function compare(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => ['required', 'array', 'size:2'],
            'ids.*' => ['required', 'integer', 'exists:estimations,id'],
        ]);

        $estimations = Estimation::query()
            ->whereIn('id', $request->input('ids'))
            ->get();

        foreach ($estimations as $estimation) {
            $this->authorize('view', $estimation);
        }

        $result = $estimations->map(fn (Estimation $e) => [
            'id' => $e->id,
            'quote_number' => $e->quote_number,
            'revision_no' => $e->revision_no,
            'building_name' => $e->building_name,
            'status' => $e->status,
            'total_weight_mt' => $e->total_weight_mt,
            'total_price_aed' => $e->total_price_aed,
            'summary' => $e->results_data['summary'] ?? null,
            'input_data' => $e->input_data,
        ]);

        return response()->json(['data' => $result]);
    }

    /**
     * Bulk export multiple estimations as a ZIP of PDFs.
     */
    public function bulkExport(BulkExportRequest $request): JsonResponse|Response|\Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $ids = $request->validated('ids');
        $sheets = $request->validated('sheets');

        $estimations = Estimation::query()
            ->whereIn('id', $ids)
            ->get();

        foreach ($estimations as $estimation) {
            $this->authorize('view', $estimation);
        }

        $calculatedEstimations = $estimations->filter(
            fn (Estimation $e) => $e->isCalculated() || $e->status === 'finalized'
        );

        if ($calculatedEstimations->isEmpty()) {
            return response()->json([
                'message' => 'None of the selected estimations have been calculated.',
            ], 422);
        }

        $sheetConfig = [
            'recap' => ['view' => 'pdf.recap', 'varName' => 'recapData', 'dataKey' => 'summary', 'paper' => 'portrait'],
            'detail' => ['view' => 'pdf.detail', 'varName' => 'detailData', 'dataKey' => 'detail', 'paper' => 'landscape'],
            'fcpbs' => ['view' => 'pdf.fcpbs', 'varName' => 'fcpbsData', 'dataKey' => 'fcpbs', 'paper' => 'landscape'],
            'sal' => ['view' => 'pdf.sal', 'varName' => 'salData', 'dataKey' => 'sal', 'paper' => 'landscape'],
            'boq' => ['view' => 'pdf.boq', 'varName' => 'boqData', 'dataKey' => 'boq', 'paper' => 'landscape'],
            'jaf' => ['view' => 'pdf.jaf', 'varName' => 'jafData', 'dataKey' => 'jaf', 'paper' => 'portrait'],
        ];

        $pdfSettings = app(PdfSettingsService::class);
        $tempFile = tempnam(sys_get_temp_dir(), 'bulk_export_');
        $zip = new \ZipArchive;
        $zip->open($tempFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        foreach ($calculatedEstimations as $estimation) {
            $quoteLabel = $estimation->quote_number ?? "EST-{$estimation->id}";
            foreach ($sheets as $sheet) {
                $config = $sheetConfig[$sheet];
                $sheetData = $estimation->results_data[$config['dataKey']] ?? null;
                if (! $sheetData) {
                    continue;
                }

                $pdf = Pdf::loadView($config['view'], $this->getPdfViewData($estimation, $config['varName'], $sheetData))
                    ->setPaper($pdfSettings->paperSize(), $config['paper']);

                $filename = strtoupper($sheet).'-'.$quoteLabel.'.pdf';
                $zip->addFromString("{$quoteLabel}/{$filename}", $pdf->output());
            }
        }

        $zip->close();

        activity()
            ->causedBy($request->user())
            ->log('bulk exported '.count($ids).' estimations');

        return response()->download($tempFile, 'estimations-export.zip', [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Build the common view data array for PDF rendering with dynamic settings.
     *
     * @return array<string, mixed>
     */
    private function getPdfViewData(Estimation $estimation, string $sheetVarName, mixed $sheetData): array
    {
        $pdfSettings = app(PdfSettingsService::class);

        return [
            'estimation' => $estimation,
            $sheetVarName => $sheetData,
            'companyName' => $pdfSettings->companyName(),
            'logoPath' => $pdfSettings->logoAbsolutePath(),
            'fontFamilyCss' => $pdfSettings->fontFamilyCss(),
            'headerColor' => $pdfSettings->headerColor(),
            'showPageNumbers' => $pdfSettings->showPageNumbers(),
            'footerText' => $pdfSettings->footerText(),
        ];
    }

    /**
     * Extract sheet data from a calculated estimation's results.
     */
    private function getSheetData(Estimation $estimation, string $sheetKey): JsonResponse
    {
        $this->authorize('view', $estimation);

        if (! $estimation->isCalculated() && $estimation->status !== 'finalized') {
            return response()->json([
                'message' => 'Estimation has not been calculated yet.',
            ], 422);
        }

        return response()->json([
            'data' => $estimation->results_data[$sheetKey] ?? null,
        ]);
    }
}
