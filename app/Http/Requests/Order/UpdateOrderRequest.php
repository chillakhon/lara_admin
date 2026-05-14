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
            'client_id' => 'nullable|integer|exists:clients,id',
            'payment_method' => 'nullable|string|max:255',
            'delivery_method_id' => 'nullable|integer|exists:delivery_methods,id',
            'delivery_date' => 'nullable|date',
            'delivery_comment' => 'nullable|string|max:1000',
            'source' => 'nullable|string|max:255',

            // Контактная информация
            'user' => 'nullable|array',
            'user.first_name' => 'nullable|string|max:255',
            'user.last_name' => 'nullable|string|max:255',
            'user.phone' => 'nullable|string|max:20',

            // Получатель (на update все nullable, чтобы можно было править частично)
            'recipient' => 'nullable|array',
            'recipient.first_name' => 'nullable|string|max:255',
            'recipient.last_name' => 'nullable|string|max:255',
            'recipient.middle_name' => 'nullable|string|max:255',
            'recipient.phone' => 'nullable|string|max:32',

            // Метод доставки
            'delivery_method' => 'nullable|array',
            'delivery_method.name' => 'nullable|string|max:255',

            // Адрес доставки
            'delivery_address' => 'nullable|array',
            'delivery_address.country' => 'nullable|string|max:255',
            'delivery_address.region' => 'nullable|string|max:255',
            'delivery_address.city' => 'nullable|string|max:255',
            'delivery_address.postal_code' => 'nullable|string|max:20',
            'delivery_address.address' => 'nullable|string|max:1000',
            'delivery_address.entrance' => 'nullable|string|max:50',
            'delivery_address.floor' => 'nullable|string|max:50',
            'delivery_address.intercom' => 'nullable|string|max:50',
            'delivery_address.delivery_comment' => 'nullable|string|max:1000',
            'delivery_address.delivery_date' => 'nullable|date',
            'delivery_address.buyer_comment' => 'nullable|string|max:1000',

            // Товары
            'items' => 'nullable|array',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.variant_id' => 'nullable|integer',
            'items.*.product_variant_id' => 'nullable|integer',
            'items.*.color_id' => 'nullable|integer|exists:colors,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',

            // Статусы
            'status' => 'nullable|string|in:'.implode(',', \App\Enums\OrderStatus::values()),
            'payment_status' => 'nullable|string|in:'.implode(',', \App\Enums\PaymentStatus::values()),

            // Дата оплаты (можно править вручную из админки)
            'paid_at' => 'nullable|date',

            // Промокод (купон). Пустая строка/null = снять купон.
            'promo_code' => 'nullable|string|max:50',

            // Прикреплённый менеджер (пользователь из раздела «Роли»).
            // null = открепить менеджера от заказа.
            'assigned_user_id' => 'nullable|integer|exists:users,id',

            // Кастомные поля заказа («Поля заказа»). Плоский массив key => value,
            // ключи/типы валидируются в OrderCustomFieldsService через реестр.
            'custom_fields' => 'nullable|array',
        ];
    }

    /**
     * Сообщения об ошибках валидации
     */
    public function messages(): array
    {
        return [
            'client_id.exists' => 'Указанный клиент не существует',
            'delivery_method_id.exists' => 'Указанный метод доставки не существует',
            'payment_status.in' => 'Недопустимый статус оплаты',
            'items.*.product_id.required' => 'ID товара обязателен',
            'items.*.product_id.exists' => 'Товар не найден',
            'items.*.quantity.required' => 'Количество обязательно',
            'items.*.quantity.min' => 'Количество должно быть не менее 1',
            'items.*.price.required' => 'Цена обязательна',
            'items.*.price.min' => 'Цена не может быть отрицательной',
        ];
    }

    /**
     * Получить валидированные данные
     */
    public function validated($key = null, $default = null): array
    {
        return parent::validated($key, $default);
    }
}
