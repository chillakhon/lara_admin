<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Client;
use App\Models\User;
use App\Notifications\MailNotification;
use Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use OpenApi\Annotations as OA;


class AuthenticatedSessionController extends Controller
{
    /**
     * Handle an incoming authentication request.
     *
     * @OA\Post(
     *     path="/api/login",
     *     summary="Authenticate a user",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "password"},
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="password")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Authenticated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Authenticated successfully"),
     *             @OA\Property(property="user", type="object", ref="#/components/schemas/User"),
     *             @OA\Property(property="token", type="string", example="1|abcdef123456")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Invalid credentials",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The provided credentials are incorrect.")
     *         )
     *     )
     * )
     */


    // for admin login
    public function admin_login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            $token = $user->createToken('authToken')->plainTextToken;

            return response()->json([
                'message' => 'Authenticated successfully',
                'user' => $user,
                'token' => $token,
            ]);
        }

        return response()->json([
            'message' => 'The provided credentials are incorrect.',
        ], 401);
    }


    // for users register


    // for users login
    public function login(Request $request)
    {
        $validation = $request->validate([
            'email' => 'required|string',
        ]);

        $user = User::where('email', $validation['email'])->first();

        if (!$user) {
            $user = User::create(['email' => $validation['email']]);

            Client::create(['user_id' => $user->id, 'bonus_balance' => 0.0]);
        }

        $user->verification_code = rand(1000, 9999);
        $user->verification_sent = now();
        $user->save();

        Notification::route('mail', $user->email)->notify(new MailNotification(
            $user->email,
            $user->verification_code
        ));

        return response()->json([
            'success' => true,
            'message' => 'На ваш email был отправлен код',
        ]);
    }

    public function check_verification(Request $request)
    {

        $validation = $request->validate([
            'email' => "required|string",
            'verification_code' => 'required|string',
        ]);

        $user = User::where('email', $validation['email'])->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Пользователь с таким email не найден.',
            ], 401);
        }

        if ($user->verification_code !== $validation['verification_code']) {
            return response()->json([
                'success' => false,
                'message' => "Неверный код подтверждения."
            ], 401);
        }

        $user->email_verified_at = now();
        $user->verified_at = now();
        $user->save();

        $token = $user->createToken('authToken')->plainTextToken;

        return response()->json([
            'message' => 'Вход успешно выполнен.',
            'user' => $user,
            'token' => $token,
        ]);
    }

    /**
     * Destroy an authenticated session.
     *
     * @OA\Post(
     *     path="/api/logout",
     *     summary="Logout a user",
     *     tags={"Authentication"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Logged out successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Logged out successfully")
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
    public function destroy(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }
}
