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
        Log::info('User role: ' . ($request->user() && $request->user()->role ? $request->user()->role->name : 'No role'));
        Log::info('Required roles: ' . implode(', ', $roles));

        // Если список ролей пуст или пользователь имеет необходимую роль, пропускаем
        if (empty($roles) || ($request->user() && $request->user()->hasAnyRole($roles))) {
            Log::info('Access granted');
            return $next($request);
        }

        Log::info('Access denied');
        // Перенаправляем на главную страницу дашборда
        return redirect()->route('dashboard.index')->with('error', 'У вас нет прав для доступа к этой странице.');
    }
}
