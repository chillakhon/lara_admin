<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class EmailVerificationPromptController extends Controller
{
    /**
     * Display the email verification prompt.
     *
     * @OA\Get(
     *     path="/api/verify-email",
     *     summary="Check if email is verified",
     *     tags={"Authentication"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Email is verified",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Email is already verified.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Email is not verified",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Please verify your email address.")
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
    public function __invoke(Request $request): JsonResponse
    {
        // Проверка, подтверждён ли email пользователя
        if ($request->user()->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Email is already verified.',
            ]);
        }

        // Если email не подтверждён
        return response()->json([
            'message' => 'Please verify your email address.',
        ], 403);
    }
}
