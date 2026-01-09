<?php

namespace App\Http\Requests\GiftCard;

use App\Models\GiftCard\GiftCard;
use Illuminate\Foundation\Http\FormRequest;

class ApplyGiftCardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => [
                'required',
                'string',
                'size:12', // Код формата 71SA7DD7GT12
                'exists:gift_cards,code',
                function ($attribute, $value, $fail) {
                    $giftCard = GiftCard::where('code', $value)->first();

                    if (!$giftCard) {
                        $fail('Подарочная карта с таким кодом не найдена.');
                        return;
                    }

                    if ($giftCard->status === GiftCard::STATUS_CANCELLED) {
                        $fail('Эта подарочная карта была аннулирована.');
                        return;
                    }

                    if ($giftCard->status === GiftCard::STATUS_USED) {
                        $fail('Эта подарочная карта уже полностью использована.');
                        return;
                    }

                    if ($giftCard->balance <= 0) {
                        $fail('На этой подарочной карте недостаточно средств.');
                        return;
                    }
                },
            ],
            'order_total' => 'required|numeric|min:0', // Сумма заказа для проверки
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

    /**
     * Получить подарочную карту по коду
     */
    public function getGiftCard(): ?GiftCard
    {
        return GiftCard::where('code', $this->input('code'))->first();
    }
}
