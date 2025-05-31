<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\User;
use App\Models\UserProfile;
use App\Notifications\MailNotification;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Notification;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function admin_registration(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'nullable|string',
            'email' => 'required|string|lowercase|email|max:255|unique:' . User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'type' => User::TYPE_CLIENT
        ]);

        if ($request->get('first_name') || $request->get('last_name')) {
            $check_for_profile = null;
            $check_for_client_with_same_email = Client::whereNull('deleted_at')
                ->where('email', $request->get('email'))
                ->first();

            if ($check_for_client_with_same_email) {
                $check_for_profile = UserProfile
                    ::where('client_id', $check_for_client_with_same_email->id)
                    ->first();
            }

            if ($check_for_profile) {
                $check_for_profile->update([
                    'user_id' => $user->id,
                    'first_name' => $request->get('first_name'),
                    'last_name' => $request->get('last_name'),
                ]);
            } else {
                UserProfile::create([
                    'user_id' => $user->id,
                    'first_name' => $request->get('first_name'),
                    'last_name' => $request->get('last_name'),
                ]);
            }
        }


        $token = $user->createToken('authToken')->plainTextToken;
        // event(new Registered($user));

        // Auth::login($user);

        // return redirect(route('dashboard', absolute: false));

        return response()->json([
            'success' => true,
            'user' => $user->load(['profile']),
            'token' => $token,
        ]);
    }

    // users
    public function register(Request $request)
    {
        $validation = $request->validate([
            'email' => 'required|string',
            'password' => 'required|string'
        ]);

        $client = Client::where('email', $validation['email'])->first();

        if ($client) {
            return response()->json([
                'success' => false,
                'message' => 'Пользователь с таким email уже зарегистрирован.',
            ], 401);
        }

        $client = Client::create([
            'email' => $validation['email'],
            'password' => Hash::make($validation['password']),
            'bonus_balance' => 0.0,
            'verification_code' => rand(1000, 9999),
            'verification_sent' => now(),
        ]);

        Notification::route('mail', $client->email)->notify(new MailNotification(
            $client->email,
            $client->verification_code
        ));

        // send email notification password
        return response()->json([
            'success' => true,
            'message' => 'Код подтверждения был отправлен на ваш email.',
        ]);
    }
}
