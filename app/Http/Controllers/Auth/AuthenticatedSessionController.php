<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthenticatedSessionController extends Controller
{
    /**
     * Показать инструкцию по логину (для API).
     */
    public function create(): JsonResponse
    {
        return response()->json([
            'message' => 'Для входа отправьте POST-запрос на /api/login с полями email и password'
        ], 200);
    }

    /**
     * Обработка аутентификации.
     */
    public function store(LoginRequest $request): JsonResponse
    {
        // В LoginRequest уже выполнена валидация
        if (! Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        // Если используете сессии (stateful):
        $request->session()->regenerate();

        // Или, если вы переходите на токен-базированную аутентификацию (например, Sanctum):
        // $token = Auth::user()->createToken('api')->plainTextToken;

        return response()->json([
            'message' => 'Успешная аутентификация',
            'user'    => Auth::user(),
            // 'token'   => $token,
        ], 200);
    }

    /**
     * Выход из сессии / аннулирование токена.
     */
    public function destroy(Request $request): JsonResponse
    {
        // При сессиях
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // При токенах (если используете Sanctum)
        // $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Вы успешно вышли'
        ], 200);
    }
}
