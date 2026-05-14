<?php

namespace App\Http\Requests\GiftCard;

use Illuminate\Foundation\Http\FormRequest;

class ResendGiftCardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->hasAnyRole(['admin', 'super-admin']);
    }

    public function rules(): array
    {
        return [
            'delivery_channel' => 'required|in:email,whatsapp,sms',
            'recipient_email' => 'required_if:delivery_channel,email|email|max:255',
//            'recipient_phone' => 'required_if:delivery_channel,whatsapp,sms|string|max:20',
        ];
    }

    public function messages(): array
    {
        return [
            'delivery_channel.required' => 'Выберите канал доставки',
            'recipient_email.required_if' => 'Укажите email получателя',
            'recipient_phone.required_if' => 'Укажите телефон получателя',
        ];
    }
}
