<?php

namespace App\Http\Requests\GiftCard;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;

class CreateGiftCardOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Основные поля заказа (если нужны)
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.variant_id' => 'nullable|exists:product_variants,id',
            'items.*.quantity' => 'required|integer|min:1',

            // Данные подарочной карты (только если в корзине есть электронный сертификат)
            'gift_card_data' => 'nullable|array',
            'gift_card_data.recipient_type' => 'required_with:gift_card_data|in:self,someone',

            // Отправитель (всегда обязателен для электронной карты)
            'gift_card_data.sender_name' => 'required_with:gift_card_data|string|max:255',
            'gift_card_data.sender_email' => 'nullable|email|max:255',
            'gift_card_data.sender_phone' => 'nullable|string|max:20',

            // Получатель (обязателен если recipient_type = someone)
            'gift_card_data.recipient_name' => 'required_if:gift_card_data.recipient_type,someone|string|max:255',
            'gift_card_data.recipient_email' => 'required_if:gift_card_data.recipient_type,someone|email|max:255',
            'gift_card_data.recipient_phone' => 'nullable|string|max:20',

            // Сообщение (опционально)
            'gift_card_data.message' => 'nullable|string|max:500',

            // Канал доставки
            'gift_card_data.delivery_channel' => 'required_with:gift_card_data|in:email,whatsapp,sms',

            // Время отправки
            'gift_card_data.delivery_type' => 'required_with:gift_card_data|in:immediate,scheduled',
            'gift_card_data.scheduled_date' => 'required_if:gift_card_data.delivery_type,scheduled|date|after:now',
            'gift_card_data.scheduled_time' => 'required_if:gift_card_data.delivery_type,scheduled|date_format:H:i',
            'gift_card_data.timezone' => 'required_if:gift_card_data.delivery_type,scheduled|string|max:50',

            // Способ оплаты
            'payment_method' => 'required|in:card,sbp,crypto',
        ];
    }

    public function messages(): array
    {
        return [
            'gift_card_data.recipient_type.required_with' => 'Укажите, кому предназначена карта',
            'gift_card_data.sender_name.required_with' => 'Укажите имя отправителя',
            'gift_card_data.recipient_name.required_if' => 'Укажите имя получателя',
            'gift_card_data.recipient_email.required_if' => 'Укажите email получателя',
            'gift_card_data.delivery_channel.required_with' => 'Выберите канал доставки',
            'gift_card_data.scheduled_date.after' => 'Дата отправки должна быть в будущем',
        ];
    }

    /**
     * Проверка: есть ли в заказе подарочный сертификат
     */
    public function hasGiftCardProduct(): bool
    {
        $items = $this->input('items', []);

        foreach ($items as $item) {
            $product = Product::find($item['product_id']);
            if ($product && $product->name === 'Подарочный сертификат') {
                return true;
            }
        }

        return false;
    }

    /**
     * Получить данные подарочной карты
     */
    public function getGiftCardData(): ?array
    {
        return $this->input('gift_card_data');
    }
}
