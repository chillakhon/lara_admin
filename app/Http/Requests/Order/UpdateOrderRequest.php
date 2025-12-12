<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderRequest extends FormRequest
{
    /**
     * Определить, авторизован ли пользователь для этого запроса
     */
    public function authorize(): bool
    {
        // Авторизацию проверяем в контроллере через OrderAuthorizationService
        return true;
    }

    /**
     * Правила валидации
     */
    public function rules(): array
    {
        return [
            'notes' => 'nullable|string|max:1000',
//            'country_code' => 'nullable|string|size:2',
//            'city_name' => 'nullable|string|max:255',
//            'delivery_address' => 'nullable|string|max:500',
//            'first_name' => 'nullable|string|max:255',
//            'last_name' => 'nullable|string|max:255',
//            'phone' => 'nullable|string|max:20',
//            'delivery_method_id' => 'nullable|integer|exists:delivery_methods,id',


            // Добавляем статусы
            'status' => 'nullable|string|in:' . implode(',', \App\Enums\OrderStatus::values()),
            'payment_status' => 'nullable|string|in:' . implode(',', \App\Enums\PaymentStatus::values()),
        ];
    }

    /**
     * Сообщения об ошибках валидации
     */
    public function messages(): array
    {
        return [
            'country_code.size' => 'Код страны должен состоять из 2 символов',
            'delivery_method_id.exists' => 'Указанный метод доставки не существует',

            'status.in' => 'Недопустимый статус заказа',
            'payment_status.in' => 'Недопустимый статус оплаты',
        ];
    }

    /**
     * Получить валидированные данные
     */
    public function validated($key = null, $default = null): array
    {
        return array_filter(parent::validated());
    }
}
