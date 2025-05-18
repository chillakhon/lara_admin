<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!$request->user()) {
            Log::info('Access denied: No authenticated user');

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            return redirect()->route('login');
        }

        Log::info('User: ' . $request->user()->id);
        Log::info('Required roles: ' . implode(', ', $roles));

        if ($request->user()->hasAnyRole($roles)) {
            Log::info('Access granted');
            return $next($request);
        }

        Log::info('Access denied: Missing required roles');

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'У вас нет необходимых ролей для доступа к этой странице.'
            ], 403);
        }

        return redirect()->route('dashboard')
            ->with('error', 'У вас нет необходимых ролей для доступа к этой странице.');
    }
}
