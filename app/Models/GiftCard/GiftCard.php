<?php

namespace App\Models\GiftCard;

use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class GiftCard extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'purchase_order_id',
        'nominal',
        'balance',
        'type',
        'status',
        'sender_name',
        'sender_email',
        'sender_phone',
        'recipient_name',
        'recipient_email',
        'recipient_phone',
        'message',
        'delivery_channel',
        'scheduled_at',
        'timezone',
        'sent_at',
        'delivered_at',
    ];

    protected $casts = [
        'nominal' => 'decimal:2',
        'balance' => 'decimal:2',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    // Статусы
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_USED = 'used';
    public const STATUS_CANCELLED = 'cancelled';

    // Типы
    public const TYPE_ELECTRONIC = 'electronic';
    public const TYPE_PLASTIC = 'plastic';

    // Каналы доставки
    public const CHANNEL_EMAIL = 'email';
    public const CHANNEL_WHATSAPP = 'whatsapp';
    public const CHANNEL_SMS = 'sms';

    /**
     * Заказ, в котором была куплена карта
     */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'purchase_order_id');
    }

    /**
     * История транзакций по карте
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(GiftCardTransaction::class);
    }

    /**
     * Заказы, где использовалась эта карта
     */
    public function usedInOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'gift_card_id');
    }

    /**
     * Проверка: активна ли карта
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE && $this->balance > 0;
    }

    /**
     * Проверка: можно ли использовать карту на указанную сумму
     */
    public function canUse(float $amount): bool
    {
        return $this->isActive() && $this->balance >= $amount;
    }

    /**
     * Проверка: полностью ли использована карта
     */
    public function isFullyUsed(): bool
    {
        return $this->balance <= 0;
    }

    /**
     * Списать деньги с карты
     */
    public function deduct(float $amount): void
    {
        $this->balance -= $amount;

        if ($this->balance <= 0) {
            $this->balance = 0;
            $this->status = self::STATUS_USED;
        }

        $this->save();
    }

    /**
     * Восстановить баланс (при возврате)
     */
    public function refund(float $amount): void
    {
        $this->balance += $amount;

        // Если баланс восстановился, делаем карту снова активной
        if ($this->status === self::STATUS_USED && $this->balance > 0) {
            $this->status = self::STATUS_ACTIVE;
        }

        $this->save();
    }

    /**
     * Аннулировать карту
     */
    public function cancel(): void
    {
        $this->status = self::STATUS_CANCELLED;
        $this->save();
    }

    /**
     * Отметить как отправленную
     */
    public function markAsSent(): void
    {
        $this->sent_at = now();
        $this->save();
    }

    /**
     * Отметить как доставленную
     */
    public function markAsDelivered(): void
    {
        $this->delivered_at = now();
        $this->save();
    }
}
