<?php

namespace App\Http\Middleware;

use App\Services\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TenantScope
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        TenantContext::set(
            $user?->tenant_id,
            $user?->role?->name,
        );

        return $next($request);
    }
}
