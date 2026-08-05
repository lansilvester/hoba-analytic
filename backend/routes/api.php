<?php

use App\Http\Controllers\ArticleController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\KeywordController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register']);

Route::middleware(['auth:sanctum', 'tenant.scope'])->group(function () {
    Route::get('/auth/user', [AuthController::class, 'user']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::get('/projects', [ProjectController::class, 'index']);
    Route::post('/projects', [ProjectController::class, 'store'])->middleware('role:admin,editor');
    Route::get('/projects/{project}', [ProjectController::class, 'show']);
    Route::put('/projects/{project}', [ProjectController::class, 'update'])->middleware('role:admin,editor');
    Route::delete('/projects/{project}', [ProjectController::class, 'destroy'])->middleware('role:admin');

    Route::get('/projects/{project}/keywords', [KeywordController::class, 'index']);
    Route::post('/projects/{project}/keywords', [KeywordController::class, 'store'])->middleware('role:admin,editor');
    Route::put('/keywords/{keyword}', [KeywordController::class, 'update'])->middleware('role:admin,editor');
    Route::delete('/keywords/{keyword}', [KeywordController::class, 'destroy'])->middleware('role:admin,editor');

    Route::get('/articles', [ArticleController::class, 'index']);
    Route::get('/articles/trends', [ArticleController::class, 'trends']);
    Route::get('/articles/{article}', [ArticleController::class, 'show']);

    Route::get('/reports', [ReportController::class, 'index']);
    Route::post('/reports/generate', [ReportController::class, 'generate'])->middleware('role:admin,editor');
    Route::get('/reports/{report}', [ReportController::class, 'show']);
    Route::get('/reports/{report}/download', [ReportController::class, 'download']);
    Route::delete('/reports/{report}', [ReportController::class, 'destroy'])->middleware('role:admin');
});
