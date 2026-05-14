<?php

namespace App\Http\Requests\GiftCard;

use Illuminate\Foundation\Http\FormRequest;

class CancelGiftCardRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Проверяем права админа
        return $this->user() && $this->user()->hasAnyRole(['admin', 'super-admin']);
    }

    public function rules(): array
    {
        return [
            'reason' => 'required|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'Укажите причину аннулирования',
            'reason.max' => 'Причина не может быть длиннее 500 символов',
        ];
    }
}
