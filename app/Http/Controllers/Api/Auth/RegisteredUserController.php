<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\User;
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
    public function admin_registration(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:' . User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'type' => User::TYPE_CLIENT
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }

    // users
    public function register(Request $request)
    {
        $validation = $request->validate([
            'email' => 'required|string',
            'password' => 'required|string'
        ]);

        $user = User::where('email', $validation['email'])->first();

        if ($user) {
            return response()->json([
                'success' => false,
                'message' => 'Пользователь с таким email уже зарегистрирован.',
            ], 401);
        }

        $user = User::create([
            'email' => $validation['email'],
            'password' => Hash::make($validation['password']),
            'verification_code' => rand(1000, 9999),
            'verification_sent' => now(),
        ]);

        Client::create(['user_id' => $user->id, 'bonus_balance' => 0.0]);

        Notification::route('mail', $user->email)->notify(new MailNotification(
            $user->email,
            $user->verification_code
        ));

        // send email notification password

        return response()->json([
            'success' => true,
            'message' => 'Код подтверждения был отправлен на ваш email.',
        ]);
    }
}
