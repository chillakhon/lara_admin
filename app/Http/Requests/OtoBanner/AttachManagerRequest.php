<?php

namespace App\Http\Requests\OtoBanner;

use Illuminate\Foundation\Http\FormRequest;

class AttachManagerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'manager_id' => ['required', 'integer', 'exists:users,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'manager_id.required' => 'Менеджер обязателен',
            'manager_id.exists' => 'Менеджер не найден',
        ];
    }
}
