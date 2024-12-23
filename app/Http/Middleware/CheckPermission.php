<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function handle(Request $request, Closure $next, ...$permissions): Response
    {
        if (!$request->user()) {
            Log::info('Access denied: No authenticated user');
            return redirect()->route('login');
        }

        Log::info('User: ' . $request->user()->id);
        Log::info('Required permissions: ' . implode(', ', $permissions));

        if ($request->user()->hasAnyPermission($permissions)) {
            Log::info('Access granted');
            return $next($request);
        }

        Log::info('Access denied: Missing required permissions');
        return redirect()->route('dashboard')
            ->with('error', 'У вас нет необходимых разрешений для доступа к этой странице.');
    }
}
