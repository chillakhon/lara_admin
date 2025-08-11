<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    // CheckRole.php
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!$request->user()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if (!$request->user()->hasAnyRole($roles)) {
            return response()->json([
                'message' => 'У вас нет необходимых ролей для этого действия.'
            ], 403);
        }

        return $next($request);
    }
}
