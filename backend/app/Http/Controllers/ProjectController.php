<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use App\Services\ProjectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function __construct(protected ProjectService $projectService) {}

    public function index(Request $request)
    {
        return ProjectResource::collection($this->projectService->paginate($request));
    }

    public function store(StoreProjectRequest $request): JsonResponse
    {
        $project = $this->projectService->create($request->validated());

        return (new ProjectResource($project))
            ->response()
            ->setStatusCode(JsonResponse::HTTP_CREATED);
    }

    public function show(Project $project): JsonResponse
    {
        return response()->json([
            'data' => new ProjectResource($project->load(['keywords', 'sources'])),
        ]);
    }

    public function update(UpdateProjectRequest $request, Project $project): JsonResponse
    {
        $project = $this->projectService->update($project, $request->validated());

        return response()->json(['data' => new ProjectResource($project)]);
    }

    public function destroy(Project $project): JsonResponse
    {
        $this->projectService->delete($project);

        return response()->json(['message' => 'Project deleted successfully']);
    }
}
