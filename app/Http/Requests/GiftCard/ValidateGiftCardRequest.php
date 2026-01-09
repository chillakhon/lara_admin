<?php

namespace App\Http\Requests\GiftCard;

use Illuminate\Foundation\Http\FormRequest;

class ValidateGiftCardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => 'required|string|size:12|exists:gift_cards,code',
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Введите код подарочной карты',
            'code.size' => 'Код должен состоять из 12 символов',
            'code.exists' => 'Подарочная карта с таким кодом не найдена',
        ];
    }
}
