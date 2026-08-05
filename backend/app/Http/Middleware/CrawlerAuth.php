<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CrawlerAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->header('X-Crawler-Token');

        if ($token === null || ! hash_equals((string) config('services.crawler.token'), $token)) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        return $next($request);
    }
}
