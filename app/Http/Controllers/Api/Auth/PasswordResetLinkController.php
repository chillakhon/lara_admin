<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Traits\HelperTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\JsonResponse;

class PasswordResetLinkController extends Controller
{

    use HelperTrait;
    /**
     * Handle an incoming password reset link request.
     *
     * @throws \Illuminate\Validation\ValidationException
     *
     * @OA\Post(
     *     path="/api/forgot-password",
     *     summary="Request a password reset link",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email"},
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Reset link sent successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="We have emailed your password reset link.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object", example={"email": {"The email field is required."}})
     *         )
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        // Валидация входных данных
        $request->validate([
            'email' => 'required|email',
        ]);


        $this->applyMailSettings();

        // Отправка ссылки для сброса пароля
        $status = Password::sendResetLink(
            $request->only('email')
        );

        // Если ссылка отправлена успешно
        if ($status == Password::RESET_LINK_SENT) {
            return response()->json([
                'status' => 'Ссылка для сброса пароля отправлена на email', // Сообщение об успешной отправке
            ]);
        }

        // Если произошла ошибка (например, email не найден)
        throw ValidationException::withMessages([
            'email' => [trans($status)],
        ]);
    }
}
