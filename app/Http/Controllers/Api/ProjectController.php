<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreProjectRequest;
use App\Http\Requests\Api\UpdateProjectRequest;
use App\Http\Resources\Api\EstimationResource;
use App\Http\Resources\Api\ProjectResource;
use App\Models\Estimation;
use App\Models\Project;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a paginated listing of projects.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Project::class);

        $query = $request->user()->isAdmin()
            ? Project::query()
            : $request->user()->projects();

        if ($request->has('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('project_name', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('project_number', 'like', "%{$search}%");
            });
        }

        $projects = $query->latest()->paginate(15);

        return ProjectResource::collection($projects)->response();
    }

    /**
     * Store a newly created project.
     */
    public function store(StoreProjectRequest $request): JsonResponse
    {
        $this->authorize('create', Project::class);

        $project = $request->user()->projects()->create(
            $request->validated()
        );

        activity()
            ->causedBy($request->user())
            ->performedOn($project)
            ->log('created project');

        return (new ProjectResource($project))
            ->additional(['message' => 'Project created successfully.'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified project.
     */
    public function show(Project $project): ProjectResource
    {
        $this->authorize('view', $project);

        $project->load('estimations');

        return new ProjectResource($project);
    }

    /**
     * Update the specified project.
     */
    public function update(UpdateProjectRequest $request, Project $project): ProjectResource
    {
        $this->authorize('update', $project);

        $project->update($request->validated());

        activity()
            ->causedBy($request->user())
            ->performedOn($project)
            ->log('updated project');

        return new ProjectResource($project->fresh());
    }

    /**
     * Soft delete the specified project.
     */
    public function destroy(Request $request, Project $project): JsonResponse
    {
        $this->authorize('delete', $project);

        activity()
            ->causedBy($request->user())
            ->performedOn($project)
            ->log('deleted project');

        $project->delete();

        return response()->json([
            'message' => 'Project deleted successfully.',
        ]);
    }

    /**
     * List buildings (estimations) in a project.
     */
    public function buildings(Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $estimations = $project->estimations()
            ->latest()
            ->get();

        return EstimationResource::collection($estimations)->response();
    }

    /**
     * Get project audit trail (last 50 activity log entries).
     */
    public function history(Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $activities = $project->activities()
            ->with('causer')
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn ($activity) => [
                'id' => $activity->id,
                'description' => $activity->description,
                'causer_name' => $activity->causer?->name,
                'created_at' => $activity->created_at?->toISOString(),
                'properties' => $activity->properties->toArray(),
            ]);

        return response()->json(['data' => $activities]);
    }

    /**
     * Add an existing estimation to a project.
     */
    public function addBuilding(Request $request, Project $project): JsonResponse
    {
        $this->authorize('update', $project);

        $request->validate([
            'estimation_id' => ['required', 'integer', 'exists:estimations,id'],
        ]);

        $estimation = Estimation::findOrFail($request->input('estimation_id'));

        if (! $request->user()->isAdmin() && $estimation->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'You do not own this estimation.',
            ], 403);
        }

        $estimation->update(['project_id' => $project->id]);

        activity()
            ->causedBy($request->user())
            ->performedOn($project)
            ->withProperties(['estimation_id' => $estimation->id])
            ->log('added building to project');

        return response()->json([
            'message' => 'Building added to project.',
            'data' => new EstimationResource($estimation->fresh()),
        ]);
    }

    /**
     * Remove an estimation from a project.
     */
    public function removeBuilding(Request $request, Project $project, Estimation $estimation): JsonResponse
    {
        $this->authorize('update', $project);

        if ($estimation->project_id !== $project->id) {
            return response()->json([
                'message' => 'This estimation does not belong to this project.',
            ], 422);
        }

        $estimation->update(['project_id' => null]);

        activity()
            ->causedBy($request->user())
            ->performedOn($project)
            ->withProperties(['estimation_id' => $estimation->id])
            ->log('removed building from project');

        return response()->json([
            'message' => 'Building removed from project.',
        ]);
    }

    /**
     * Duplicate a building (estimation) within the project.
     */
    public function duplicateBuilding(Request $request, Project $project, Estimation $estimation): JsonResponse
    {
        $this->authorize('update', $project);

        if ($estimation->project_id !== $project->id) {
            return response()->json([
                'message' => 'This estimation does not belong to this project.',
            ], 422);
        }

        $clone = $request->user()->estimations()->create([
            'project_id' => $project->id,
            'quote_number' => $estimation->quote_number,
            'revision_no' => $estimation->revision_no,
            'building_name' => $estimation->building_name.' (Copy)',
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
            ->performedOn($project)
            ->withProperties([
                'estimation_id' => $clone->id,
                'duplicated_from' => $estimation->id,
            ])
            ->log('duplicated building in project');

        return (new EstimationResource($clone))
            ->additional(['message' => 'Building duplicated successfully.'])
            ->response()
            ->setStatusCode(201);
    }
}
