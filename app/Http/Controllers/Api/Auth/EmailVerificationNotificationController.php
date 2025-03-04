<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class EmailVerificationNotificationController extends Controller
{
    /**
     * Send a new email verification notification.
     *
     * @OA\Post(
     *     path="/api/email/verification-notification",
     *     summary="Send email verification notification",
     *     tags={"Authentication"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Verification link sent",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="verification-link-sent")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Email already verified",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Email already verified.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        // Проверка, подтверждён ли email пользователя
        if ($request->user()->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Email already verified.',
            ], 400);
        }

        // Отправка письма с ссылкой для подтверждения email
        $request->user()->sendEmailVerificationNotification();

        return response()->json([
            'status' => 'verification-link-sent',
        ]);
    }
}
