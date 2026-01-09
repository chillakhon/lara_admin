<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

class CreateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            // Адрес доставки
            'country_code' => 'required|string|size:2',
            'city_name' => 'required|string|max:255',
            'delivery_address' => 'required|string|max:500',

            // Заметки
            'notes' => 'nullable|string|max:1000',

            // Промокод
            'promo_code' => 'nullable|string|max:50',

            // Контактная информация
            'user' => 'required|array',
            'user.first_name' => 'required|string|max:255',
            'user.last_name' => 'required|string|max:255',
            'user.phone' => 'required|string|max:20',

            // Товары
            'items' => 'required|array|min:1|max:50',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.product_variant_id' => 'nullable|integer|exists:product_variants,id',
            'items.*.color_id' => 'nullable|integer|exists:colors,id',
            'items.*.quantity' => 'required|integer|min:1|max:999',
            'items.*.price' => 'required|numeric|min:0|max:9999999',

            'gift_card_code' => 'nullable|string|size:12',

            'gift_card_data' => 'nullable|array',
            'gift_card_data.type' => 'required_with:gift_card_data|in:electronic,plastic',
            'gift_card_data.sender_name' => 'required_with:gift_card_data|string|min:2|max:100',
            'gift_card_data.message' => 'nullable|string|max:500',

        ];


        if ($this->input('gift_card_data.type') === 'electronic') {
            $rules['gift_card_data.recipient_type'] = 'required|in:self,someone';
            $rules['gift_card_data.delivery_channel'] = 'required|in:email,telegram';
            $rules['gift_card_data.delivery_type'] = 'required|in:immediate,scheduled';

            // Для другого получателя
            if ($this->input('gift_card_data.recipient_type') === 'someone') {
                $rules['gift_card_data.recipient_name'] = 'required|string|min:2|max:100';

                if ($this->input('gift_card_data.delivery_channel') === 'email') {
                    $rules['gift_card_data.recipient_email'] = 'required|email|max:100';
                }

                if ($this->input('gift_card_data.delivery_channel') === 'telegram') {
                    $rules['gift_card_data.recipient_phone'] = 'required|string|max:20';
                }
            }

            // Для запланированной отправки
            if ($this->input('gift_card_data.delivery_type') === 'scheduled') {
                $rules['gift_card_data.scheduled_date'] = 'required|date|after_or_equal:today';
                $rules['gift_card_data.scheduled_time'] = 'required|date_format:H:i';
                $rules['gift_card_data.timezone'] = 'required|string|max:50';
            }
        }

        return $rules;

    }

    public function messages(): array
    {
        return [
            'country_code.required' => 'Код страны обязателен',
            'country_code.size' => 'Код страны должен состоять из 2 символов',
            'city_name.required' => 'Название города обязательно',
            'delivery_address.required' => 'Адрес доставки обязателен',

            'user.required' => 'Контактная информация обязательна',
            'user.first_name.required' => 'Имя обязательно',
            'user.last_name.required' => 'Фамилия обязательна',
            'user.phone.required' => 'Телефон обязателен',

            'items.required' => 'Необходимо добавить товары в заказ',
            'items.min' => 'Необходимо добавить хотя бы один товар',
            'items.max' => 'Максимальное количество товаров в заказе: 50',



            'gift_card_data.sender_name.required_with' => 'Укажите ваше имя',
            'gift_card_data.recipient_name.required_if' => 'Укажите имя получателя',
            'gift_card_data.recipient_email.required_if' => 'Укажите email получателя',
            'gift_card_data.recipient_phone.required_if' => 'Укажите телефон получателя',
            'gift_card_data.scheduled_date.required_if' => 'Укажите дату отправки',
            'gift_card_data.scheduled_time.required_if' => 'Укажите время отправки',
            'gift_card_data.message.max' => 'Сообщение не должно превышать 500 символов',

        ];
    }
}
