<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
// CheckPermission.php
    public function handle(Request $request, Closure $next, ...$permissions): Response
    {
        if (!$request->user()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if (!$request->user()->hasAnyPermission($permissions)) {
            return response()->json([
                'message' => 'У вас нет необходимых разрешений для этого действия.'
            ], 403);
        }

        return $next($request);
    }
}
