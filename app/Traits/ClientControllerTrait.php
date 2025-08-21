<?php

namespace App\Traits;

use App\Models\Client;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

trait ClientControllerTrait
{
    public function check_users_with_same_email($client): UserProfile|null
    {
        $check_for_user_with_same_email = Client::whereNull('deleted_at')
            ->where('email', $client->email)
            ->first();

        $user_profile = null;

        if ($check_for_user_with_same_email) {
            $user_profile = UserProfile
                ::where('client_id', $check_for_user_with_same_email->id)
                ->first();
        }

        return $user_profile;
    }

    public function check_client_with_same_email($client): UserProfile|null
    {
        $check_for_user_with_same_email = Client::whereNull('deleted_at')
            ->where('email', $client->email)
            ->first();

        $user_profile = null;

        if ($check_for_user_with_same_email) {
            $user_profile = UserProfile
                ::where('client_id', $check_for_user_with_same_email->id)
                ->first();
        }

        return $user_profile;
    }


    protected function validateClientData(array $data, $client = null)
    {
        $rules = [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')
                    ->whereNull('deleted_at')
                    ->ignore($client ? $client->id : null)
            ],
            'phone' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('user_profiles', 'phone')
                    ->whereNotNull('phone')
                    ->ignore($client ? $client->profile->id : null, 'id')
            ],
            'address' => 'nullable|string|max:255',
            'birthday' => 'nullable|date',
//            'level_id' => 'nullable|exists:client_levels,id',
//            'bonus_balance' => 'nullable|numeric|min:0',
        ];

        // Добавляем правило для пароля только при создании
//        if (is_null($client)) {
//            $rules['password'] = 'required|string|min:8';
//        }

        return validator($data, $rules, [
            'email.unique' => 'Пользователь с таким email уже существует.',
            'phone.unique' => 'Пользователь с таким номером телефона уже существует.'
        ])->validate();
    }

    protected function checkExistingClientData(array $validated, $client = null)
    {
        // Проверка email (если он изменился)
        if ((!$client || $validated['email'] !== $client->email) &&
            Client::where('email', $validated['email'])->exists()) {
            throw ValidationException::withMessages([
                'email' => ['Пользователь с таким email уже существует.']
            ]);
        }

        // Проверка phone (если он изменился и не пустой)
        if (!empty($validated['phone']) &&
            (!$client || $validated['phone'] !== $client->profile->phone) &&
            UserProfile::where('phone', $validated['phone'])->exists()) {
            throw ValidationException::withMessages([
                'phone' => ['Пользователь с таким номером телефона уже существует.']
            ]);
        }
    }

}
