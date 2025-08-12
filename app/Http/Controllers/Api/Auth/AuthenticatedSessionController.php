<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Client;
use App\Models\Permission;
use App\Models\User;
use App\Models\UserPermission;
use App\Notifications\LoginNotification;
use App\Notifications\MailNotification;
use App\Notifications\WelcomeNotification;
use App\Traits\HelperTrait;
use DB;
use Exception;
use Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use OpenApi\Annotations as OA;


class AuthenticatedSessionController extends Controller
{

    use HelperTrait;

    // for admin login
    public function admin_login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            $token = $user->createToken('authToken')->plainTextToken;

            $user_permissions = UserPermission
                ::where('user_id', $user->id)
                ->with('roles', 'profile')
                ->pluck('permission_id')->toArray();

            $user['permissions'] = $user_permissions;

            return response()->json([
                'message' => 'Authenticated successfully',
                'user' => $user->load('roles', 'profile'),
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
        DB::beginTransaction();
        try {
            $validation = $request->validate([
                'email' => 'required|string',
            ]);

            $client = Client::where('email', $validation['email'])->whereNull('deleted_at')->first();

            if (!$client) {
                $client = Client::create([
                    'email' => $validation['email'],
                    'bonus_balance' => 0.0,
                ]);
            }

            $client->verification_code = rand(1000, 9999);
            $client->verification_sent = now();
            $client->save();


            $this->applyMailSettings();

            Notification::route('mail', $client->email)->notify(new MailNotification(
                $client->email,
                $client->verification_code
            ));


            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'На ваш email был отправлен код',
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function check_verification(Request $request)
    {
        $validation = $request->validate([
            'email' => "required|string",
            'verification_code' => 'required|string',
        ]);

        $client = Client::where('email', $validation['email'])
            ->whereNull('deleted_at')
            ->first();

        if (!$client) {
            return response()->json([
                'success' => false,
                'message' => 'Пользователь с таким email не найден.',
            ], 401);
        }

        if ($client->verification_code !== $validation['verification_code']) {
            return response()->json([
                'success' => false,
                'message' => "Неверный код подтверждения."
            ], 401);
        }

        // Проверка — это первый вход?
        $isFirstLogin = is_null($client->verified_at);

        // Обновляем время верификации
        $client->verified_at = now();
        $client->save();

        // Создаем токен
        $token = $client->createToken('authToken')->plainTextToken;

        // Подгружаем профиль
        $client->load('profile');

        // Отправляем нужное письмо
        if ($isFirstLogin) {
            Notification::route('mail', $client->email)->notify(
                new WelcomeNotification($client->email)
            );
        } else {
            Notification::route('mail', $client->email)->notify(
                new LoginNotification($client->email)
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Вход успешно выполнен.',
            'user' => $client,
            'token' => $token,
        ]);
    }

    public function get_user(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'user' => $user->load(['profile'])
        ]);
    }


    public function destroy(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }


    public function get_admin_user(Request $request)
    {
        $user = $request->user();

        $user_permissions = UserPermission
            ::where('user_id', $user->id)
            ->pluck('permission_id')->toArray();

        $user['permissions'] = $user_permissions;

        return $user->load('roles', 'profile');
    }
}
