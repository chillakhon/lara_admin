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
            ],
            'address' => 'nullable|string|max:255',
            'birthday' => 'nullable|date',
            'middle_name' => 'nullable|string|max:255',
            'subscribed_to_newsletter' => 'nullable|boolean',
            'personal_data_consent' => 'nullable|boolean',
            'messenger_subscription' => 'nullable|boolean',
            'delivery_region' => 'nullable|string|max:255',
            'delivery_street' => 'nullable|string|max:255',
            'delivery_house' => 'nullable|string|max:50',
            'delivery_apartment' => 'nullable|string|max:50',
            'delivery_postal_code' => 'nullable|string|max:20',
            'delivery_country_id' => 'nullable|integer|exists:country,id',
            'delivery_city_id' => 'nullable|integer|exists:city,id',
            'rfm_segment' => 'nullable|string|max:32',
            'group_name' => 'nullable|string|max:255',
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

        // Проверка phone отключена — допускаются дубликаты телефонов
    }

}
