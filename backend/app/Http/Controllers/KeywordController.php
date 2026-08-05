<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreKeywordRequest;
use App\Http\Requests\UpdateKeywordRequest;
use App\Http\Resources\KeywordResource;
use App\Models\Keyword;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KeywordController extends Controller
{
    public function index(Project $project)
    {
        return KeywordResource::collection($project->keywords()->get());
    }

    public function store(StoreKeywordRequest $request, Project $project): JsonResponse
    {
        $keyword = $project->keywords()->create($request->validated());

        return (new KeywordResource($keyword))
            ->response()
            ->setStatusCode(JsonResponse::HTTP_CREATED);
    }

    public function update(UpdateKeywordRequest $request, Keyword $keyword): JsonResponse
    {
        $keyword->update($request->validated());

        return response()->json(['data' => new KeywordResource($keyword)]);
    }

    public function destroy(Keyword $keyword): JsonResponse
    {
        $keyword->delete();

        return response()->json(['message' => 'Keyword deleted successfully']);
    }
}
