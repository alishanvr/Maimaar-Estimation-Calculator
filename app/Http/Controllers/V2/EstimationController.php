<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\EstimationResource;
use App\Models\Estimation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EstimationController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $query = $user->isAdmin()
            ? Estimation::query()
            : $user->estimations();

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        $estimations = $query->latest()->paginate(15)->withQueryString();

        return Inertia::render('Estimations/Index', [
            'estimations' => EstimationResource::collection($estimations),
            'filters' => [
                'status' => $request->query('status', ''),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $estimation = $request->user()->estimations()->create([
            'building_name' => $request->input('building_name', 'New Building'),
            'status' => 'draft',
            'input_data' => $request->input('input_data', []),
        ]);

        return redirect()->route('v2.estimations.show', $estimation)
            ->with('success', 'Estimation created.');
    }

    public function show(Estimation $estimation): Response
    {
        $estimation->load('items');

        return Inertia::render('Estimations/Show', [
            'estimation' => (new EstimationResource($estimation))->toArray(request()),
        ]);
    }

    public function destroy(Request $request, Estimation $estimation): RedirectResponse
    {
        $estimation->delete();

        return redirect()->route('v2.estimations.index')
            ->with('success', 'Estimation deleted.');
    }

    public function clone(Request $request, Estimation $estimation): RedirectResponse
    {
        $clone = $request->user()->estimations()->create([
            'building_name' => ($estimation->building_name ?? 'Building').' (Copy)',
            'project_name' => $estimation->project_name,
            'customer_name' => $estimation->customer_name,
            'salesperson_code' => $estimation->salesperson_code,
            'status' => 'draft',
            'input_data' => $estimation->input_data,
        ]);

        return redirect()->route('v2.estimations.show', $clone)
            ->with('success', 'Estimation cloned.');
    }

    public function compare(Request $request): Response
    {
        $idsParam = $request->query('ids', '');
        $ids = array_filter(array_map('intval', explode(',', $idsParam)));

        abort_if(count($ids) !== 2, 422, 'Please select exactly 2 estimations.');

        $estimations = Estimation::whereIn('id', $ids)->get();

        abort_if($estimations->count() !== 2, 404, 'One or both estimations not found.');

        $data = $estimations->map(fn (Estimation $e) => [
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

        return Inertia::render('Estimations/Compare', [
            'estimations' => $data,
        ]);
    }
}
