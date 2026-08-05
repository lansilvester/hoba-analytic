<?php

namespace App\Http\Controllers;

use App\Http\Resources\SourceResource;
use App\Models\Source;
use Illuminate\Http\JsonResponse;

class SourceController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => SourceResource::collection(Source::where('is_active', true)->orderBy('name')->get()),
        ]);
    }
}
