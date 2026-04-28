<?php

namespace App\Http\Requests\Order;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $items = $this->input('items', []);
        if (! is_array($items)) {
            return;
        }

        $normalized = [];
        foreach ($items as $item) {
            if (! is_array($item)) {
                $normalized[] = $item;

                continue;
            }
            if (array_key_exists('variant_id', $item) && ! array_key_exists('product_variant_id', $item)) {
                $item['product_variant_id'] = $item['variant_id'];
            }
            $normalized[] = $item;
        }

        $this->merge(['items' => $normalized]);
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $user = $this->user();
            if ($user instanceof User) {
                if ($this->filled('client_id') === false || $this->input('client_id') === null || $this->input('client_id') === '') {
                    $validator->errors()->add('client_id', 'Укажите клиента (client_id).');
                }
            }

            // gift_product_id обязателен только если promotion_id указан И use_discount_instead != true
            if ($this->filled('promotion_id') && ! $this->boolean('use_discount_instead')) {
                if (! $this->filled('gift_product_id')) {
                    $validator->errors()->add('gift_product_id', 'Выберите подарок для акции.');
                }
            }
        });
    }

    public function rules(): array
    {
        $rules = [
            // Адрес доставки
            'delivery_address' => 'required|array',
            'delivery_address.country' => 'required|string|max:255',
            'delivery_address.region' => 'nullable|string|max:255',
            'delivery_address.city' => 'required|string|max:255',
            'delivery_address.postal_code' => 'nullable|string|max:20',
            'delivery_address.address' => 'required|string|max:1000',
            'delivery_address.entrance' => 'nullable|string|max:50',
            'delivery_address.floor' => 'nullable|string|max:50',
            'delivery_address.intercom' => 'nullable|string|max:50',
            'delivery_address.delivery_comment' => 'nullable|string|max:1000',
            'delivery_address.delivery_date' => 'nullable|date',
            'delivery_address.buyer_comment' => 'nullable|string|max:1000',

            // Заметки
            'notes' => 'nullable|string|max:1000',

            // Промокод
            'promo_code' => 'nullable|string|max:50',

            // Акция
            'promotion_id' => 'nullable|integer|exists:promotions,id',
            'gift_product_id' => 'nullable|integer|exists:products,id',
            'use_discount_instead' => 'nullable|boolean',

            // Контактная информация
            'user' => 'required|array',
            'user.first_name' => 'required|string|max:255',
            'user.last_name' => 'required|string|max:255',
            'user.phone' => 'nullable|string|max:20',

            'client_id' => [
                'nullable',
                'integer',
                Rule::exists('clients', 'id')->whereNull('deleted_at'),
            ],

            'status' => ['nullable', 'string', Rule::in(OrderStatus::values())],
            'payment_status' => ['nullable', 'string', Rule::in(PaymentStatus::values())],

            'delivery_method' => 'nullable|array',
            'delivery_method.name' => 'nullable|string|max:255',

            // Товары
            'items' => 'required|array|min:1|max:50',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.variant_id' => 'nullable|integer|exists:product_variants,id',
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
            'delivery_address.required' => 'Адрес доставки обязателен',
            'delivery_address.country.required' => 'Страна обязательна',
            'delivery_address.city.required' => 'Город обязателен',
            'delivery_address.address.required' => 'Адрес обязателен',

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
