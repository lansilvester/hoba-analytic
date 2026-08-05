<?php

namespace App\Http\Controllers;

use App\Http\Resources\ArticleDetailResource;
use App\Http\Resources\ArticleResource;
use App\Models\Article;
use App\Services\ArticleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ArticleController extends Controller
{
    public function __construct(protected ArticleService $articleService) {}

    public function index(Request $request)
    {
        return ArticleResource::collection($this->articleService->paginate($request));
    }

    public function show(Article $article): JsonResponse
    {
        return response()->json([
            'data' => new ArticleDetailResource($article->load(['source', 'analysis'])),
        ]);
    }

    public function trends(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->articleService->trends($request)]);
    }
}
