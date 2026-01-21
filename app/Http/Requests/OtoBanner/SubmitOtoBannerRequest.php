<?php

namespace App\Http\Requests\OtoBanner;

use Illuminate\Foundation\Http\FormRequest;

class SubmitOtoBannerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'message' => ['nullable', 'string', 'max:2000'],
            'input_field_value' => ['required', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'input_field_value.required' => 'Пожалуйста, заполните поле',
            'email.email' => 'Некорректный email адрес',
        ];
    }
}
