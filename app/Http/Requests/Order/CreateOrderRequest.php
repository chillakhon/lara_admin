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
        return [
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
        ];
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
        ];
    }
}
