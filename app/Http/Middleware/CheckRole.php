<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        Log::info('User: ' . ($request->user() ? $request->user()->id : 'No user'));
        Log::info('User type: ' . ($request->user() ? $request->user()->type : 'No type'));
        Log::info('Required roles: ' . implode(', ', $roles));

        if (empty($roles) || ($request->user() && $request->user()->hasAnyRole($roles))) {
            Log::info('Access granted');
            return $next($request);
        }

        Log::info('Access denied');
        return redirect()->route('dashboard')->with('error', 'У вас нет прав для доступа к этой странице.');
    }
}
