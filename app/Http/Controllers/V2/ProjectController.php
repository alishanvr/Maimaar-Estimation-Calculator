<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\EstimationResource;
use App\Http\Resources\Api\ProjectResource;
use App\Models\Estimation;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $query = $user->isAdmin()
            ? Project::query()
            : $user->projects();

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('project_name', 'like', "%{$search}%")
                    ->orWhere('project_number', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%");
            });
        }

        $projects = $query->latest()->paginate(15)->withQueryString();

        return Inertia::render('Projects/Index', [
            'projects' => ProjectResource::collection($projects),
            'filters' => [
                'status' => $request->query('status', ''),
                'search' => $request->query('search', ''),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'project_number' => ['required', 'string', 'max:50', 'unique:projects,project_number'],
            'project_name' => ['required', 'string', 'max:255'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
        ]);

        $project = $request->user()->projects()->create($validated);

        return redirect()->route('v2.projects.show', $project)
            ->with('success', 'Project created.');
    }

    public function show(Project $project): Response
    {
        $project->load(['estimations' => fn ($q) => $q->latest()]);

        $projectData = (new ProjectResource($project))->toArray(request());
        $projectData['estimations'] = EstimationResource::collection($project->estimations)
            ->toArray(request());

        return Inertia::render('Projects/Show', [
            'project' => $projectData,
        ]);
    }

    public function update(Request $request, Project $project): RedirectResponse
    {
        $validated = $request->validate([
            'project_name' => ['sometimes', 'string', 'max:255'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => ['sometimes', 'string', 'in:draft,in_progress,completed,archived'],
        ]);

        $project->update($validated);

        return back()->with('success', 'Project updated.');
    }

    public function destroy(Request $request, Project $project): RedirectResponse
    {
        $project->delete();

        return redirect()->route('v2.projects.index')
            ->with('success', 'Project deleted.');
    }

    public function addBuilding(Request $request, Project $project): RedirectResponse
    {
        $estimation = $request->user()->estimations()->create([
            'project_id' => $project->id,
            'building_name' => 'New Building',
            'status' => 'draft',
            'input_data' => [],
        ]);

        return redirect()->route('v2.estimations.show', $estimation)
            ->with('success', 'Building added to project.');
    }

    public function duplicateBuilding(Request $request, Project $project, Estimation $estimation): RedirectResponse
    {
        $clone = $request->user()->estimations()->create([
            'project_id' => $project->id,
            'building_name' => ($estimation->building_name ?? 'Building').' (Copy)',
            'project_name' => $estimation->project_name,
            'customer_name' => $estimation->customer_name,
            'status' => 'draft',
            'input_data' => $estimation->input_data,
        ]);

        return back()->with('success', 'Building duplicated.');
    }

    public function removeBuilding(Request $request, Project $project, Estimation $estimation): RedirectResponse
    {
        $estimation->update(['project_id' => null]);

        return back()->with('success', 'Building removed from project.');
    }

    public function history(Project $project): JsonResponse
    {
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
}
