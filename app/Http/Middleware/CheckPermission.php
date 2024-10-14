<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CheckPermission
{
    public function handle(Request $request, Closure $next, ...$permissions)
    {
        Log::info('User: ' . ($request->user() ? $request->user()->id : 'No user'));
        Log::info('Required permissions: ' . implode(', ', $permissions));

        if ($request->user() && $request->user()->hasAnyPermission($permissions)) {
            Log::info('Access granted');
            return $next($request);
        }

        Log::info('Access denied');
        return redirect()->route('dashboard')->with('error', 'У вас нет необходимых разрешений для доступа к этой странице.');
    }
}
