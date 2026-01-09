<?php

namespace App\Models\GiftCard;

use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GiftCardTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'gift_card_id',
        'order_id',
        'type',
        'amount',
        'balance_before',
        'balance_after',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
    ];

    // Типы транзакций
    public const TYPE_PURCHASE = 'purchase';
    public const TYPE_USAGE = 'usage';
    public const TYPE_REFUND = 'refund';
    public const TYPE_CANCELLATION = 'cancellation';

    /**
     * Подарочная карта
     */
    public function giftCard(): BelongsTo
    {
        return $this->belongsTo(GiftCard::class);
    }

    /**
     * Заказ, связанный с транзакцией
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Создать транзакцию покупки
     */
    public static function createPurchase(GiftCard $giftCard, Order $order): self
    {
        return self::create([
            'gift_card_id' => $giftCard->id,
            'order_id' => $order->id,
            'type' => self::TYPE_PURCHASE,
            'amount' => $giftCard->nominal,
            'balance_before' => 0,
            'balance_after' => $giftCard->nominal,
            'notes' => "Карта куплена в заказе #{$order->id}",
        ]);
    }

    /**
     * Создать транзакцию использования
     */
    public static function createUsage(GiftCard $giftCard, Order $order, float $amount): self
    {
        $balanceBefore = $giftCard->balance;

        return self::create([
            'gift_card_id' => $giftCard->id,
            'order_id' => $order->id,
            'type' => self::TYPE_USAGE,
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceBefore - $amount,
            'notes' => "Использовано {$amount} ₽ в заказе #{$order->id}",
        ]);
    }

    /**
     * Создать транзакцию возврата
     */
    public static function createRefund(GiftCard $giftCard, Order $order, float $amount): self
    {
        $balanceBefore = $giftCard->balance;

        return self::create([
            'gift_card_id' => $giftCard->id,
            'order_id' => $order->id,
            'type' => self::TYPE_REFUND,
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceBefore + $amount,
            'notes' => "Возврат {$amount} ₽ из заказа #{$order->id}",
        ]);
    }

    /**
     * Создать транзакцию аннулирования
     */
    public static function createCancellation(GiftCard $giftCard, ?string $reason = null): self
    {
        return self::create([
            'gift_card_id' => $giftCard->id,
            'type' => self::TYPE_CANCELLATION,
            'amount' => 0,
            'balance_before' => $giftCard->balance,
            'balance_after' => $giftCard->balance,
            'notes' => $reason ?? 'Карта аннулирована',
        ]);
    }
}
