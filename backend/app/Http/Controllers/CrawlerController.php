<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\JsonResponse;

class CrawlerController extends Controller
{
    public function keywords(): JsonResponse
    {
        $project = Project::withoutGlobalScopes()->findOrFail(
            (int) request()->input('project_id'),
        );

        $keywords = $project->keywords()
            ->where('is_active', true)
            ->pluck('keyword')
            ->values();

        return response()->json(['data' => $keywords]);
    }
}
