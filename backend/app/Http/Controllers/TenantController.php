<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Http\JsonResponse;

class TenantController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => Tenant::orderBy('name')->get(['id', 'name']),
        ]);
    }
}
