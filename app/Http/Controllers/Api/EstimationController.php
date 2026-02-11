<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CalculateEstimationRequest;
use App\Http\Requests\Api\StoreEstimationRequest;
use App\Http\Requests\Api\UpdateEstimationRequest;
use App\Http\Resources\Api\EstimationCollection;
use App\Http\Resources\Api\EstimationResource;
use App\Models\Estimation;
use App\Services\Estimation\EstimationService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
     * Export BOQ as PDF (stub — deferred to Iteration 7).
     */
    public function exportBoq(Estimation $estimation): JsonResponse
    {
        $this->authorize('view', $estimation);

        return response()->json([
            'message' => 'PDF export will be available in a future release.',
        ], 501);
    }

    /**
     * Export JAF as PDF (stub — deferred to Iteration 7).
     */
    public function exportJaf(Estimation $estimation): JsonResponse
    {
        $this->authorize('view', $estimation);

        return response()->json([
            'message' => 'PDF export will be available in a future release.',
        ], 501);
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
