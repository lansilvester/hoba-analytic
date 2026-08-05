<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $role = Auth::user()?->role?->name;

        if ($role === null || ! in_array($role, $roles, true)) {
            return response()->json([
                'message' => 'Forbidden',
                'error' => 'insufficient_role',
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
