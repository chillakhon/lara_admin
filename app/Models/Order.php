<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\GiftCard\GiftCard;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'order_number',
        'view_token',
        'client_id',
        'lead_id',
        'status',
        'payment_status',
        'total_amount',
        'total_amount_original',
        'discount_amount',
        'total_promo_discount',
        'total_items_discount',
        'promo_code_id',
        'promotion_id',
        'payment_method',
        'payment_provider',
        'payment_id',
        'paid_at',
        'source',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_content',
        'utm_term',
        'ip_address',
        'user_agent',
        'notes',

        'delivery_method_id',
        'delivery_zone_id',
        'delivery_address',
        'delivery_cost',
        'delivery_data',
        'delivery_comment',
        'delivery_target_id',
        'delivery_date',

        'gift_card_id',
        'gift_card_amount',

        // Поля под легаси-импорт InSales
        'assigned_user_id',
        'tracking_number',
        'bonuses_credited',
        'bonuses_used',
        'no_receipt',
        'export_country',
        'utm_source_first',
        'referrer_first',
        'referrer_last',
        'landing_first',
        'landing_last',
        'legacy_meta',
        'legacy_delivery_method',
    ];

    protected $casts = [
        'status' => OrderStatus::class,
        'payment_status' => PaymentStatus::class,
        'total_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'delivery_date' => 'datetime',

        'gift_card_amount' => 'decimal:2',

        'created_at' => 'datetime',

        // Легаси-импорт
        'bonuses_credited' => 'decimal:2',
        'bonuses_used' => 'decimal:2',
        'no_receipt' => 'boolean',
        'legacy_meta' => 'array',
    ];

    public function address(): HasOne
    {
        return $this->hasOne(OrderAddress::class);
    }

    public function deliveryTarget()
    {
        return $this->belongsTo(DeliveryTarget::class, 'delivery_target_id');
    }

    public function getDeliveryAddressAttribute($value): ?array
    {
        $fallbackValue = $this->normalizeDeliveryAddressValue($value);

        if ($this->relationLoaded('address') && $this->address) {
            return $this->formatDeliveryAddressPayload($this->address);
        }

        $address = $this->address()->first();

        if ($address) {
            return $this->formatDeliveryAddressPayload($address);
        }

        return $fallbackValue;
    }

    private function normalizeDeliveryAddressValue($value): ?array
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $decodedValue = json_decode($value, true);

        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decodedValue)) {
            return [
                'country' => null,
                'region' => null,
                'city' => null,
                'postal_code' => null,
                'address' => $value,
                'entrance' => null,
                'floor' => null,
                'intercom' => null,
                'delivery_comment' => null,
                'delivery_date' => null,
                'buyer_comment' => null,
            ];
        }

        return [
            'country' => $decodedValue['country'] ?? null,
            'region' => $decodedValue['region'] ?? null,
            'city' => $decodedValue['city'] ?? null,
            'postal_code' => $decodedValue['postal_code'] ?? null,
            'address' => $decodedValue['address'] ?? $decodedValue['full_address'] ?? null,
            'entrance' => $decodedValue['entrance'] ?? null,
            'floor' => $decodedValue['floor'] ?? null,
            'intercom' => $decodedValue['intercom'] ?? null,
            'delivery_comment' => $decodedValue['delivery_comment'] ?? null,
            'delivery_date' => $decodedValue['delivery_date'] ?? null,
            'buyer_comment' => $decodedValue['buyer_comment'] ?? null,
        ];
    }

    private function formatDeliveryAddressPayload(OrderAddress $address): array
    {
        return [
            'country' => $address->country,
            'region' => $address->region,
            'city' => $address->city,
            'postal_code' => $address->postal_code,
            'address' => $address->address,
            'entrance' => $address->entrance,
            'floor' => $address->floor,
            'intercom' => $address->intercom,
            'delivery_comment' => $address->delivery_comment,
            'delivery_date' => $address->delivery_date?->format('Y-m-d H:i:s'),
            'buyer_comment' => $address->buyer_comment,
            // Получатель хранится здесь же — отдаём фронту как часть delivery_address,
            // а также отдельным полем 'recipient' через accessor ниже.
            'recipient_first_name' => $address->recipient_first_name,
            'recipient_last_name' => $address->recipient_last_name,
            'recipient_middle_name' => $address->recipient_middle_name,
            'recipient_phone' => $address->recipient_phone,
        ];
    }

    public function deliveryMethod(): BelongsTo
    {
        return $this->belongsTo(DeliveryMethod::class, 'delivery_method_id');
    }

    public function deliveryZone()
    {
        return $this->belongsTo(DeliveryZone::class, 'delivery_zone_id');
    }

    public function deliveryDate(): HasOne
    {
        return $this->hasOne(DeliveryDate::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Менеджер, прикреплённый к заказу (пользователь из admin-панели).
     */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'assigned_user_id');
    }

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function history()
    {
        return $this->hasMany(OrderHistory::class);
    }

    public function payments()
    {
        return $this->hasMany(OrderPayment::class);
    }

    public function payment()
    {
        return $this->hasOne(OrderPayment::class);
    }

    public function promoCode()
    {
        return $this->belongsTo(PromoCode::class);
    }

    public function promotion()
    {
        return $this->belongsTo(Promotion::class);
    }

    public function updatePaymentStatus(PaymentStatus|string $status, ?string $paymentId = null)
    {
        if (is_string($status)) {
            $status = PaymentStatus::from($status);
        }

        $this->payment_status = $status;

        if ($status === PaymentStatus::PAID) {
            $this->paid_at = now();
            $this->payment_id = $paymentId;
        }

        $this->save();

        // Записываем в историю
        $this->history()->create([
            'payment_status' => $status->value,
            'comment' => "Статус оплаты изменен на: {$status->label()}",
            'user_id' => auth()->id(),
        ]);
    }

    public function isPaid(): bool
    {
        return $this->payment_status === PaymentStatus::PAID;
    }

    public function canBePaid(): bool
    {
        return in_array($this->payment_status, [
            PaymentStatus::PENDING,
            PaymentStatus::FAILED,
        ]);
    }

    public function updateTotalAmount(): void
    {
        $this->total_amount = $this->items()->sum(DB::raw('quantity * price'));
        $this->save();
    }

    /**
     * Подарочная карта, использованная в заказе
     */
    public function giftCard(): BelongsTo
    {
        return $this->belongsTo(GiftCard::class);
    }

    /**
     * Проверка: использовалась ли подарочная карта
     */
    public function hasGiftCard(): bool
    {
        return ! is_null($this->gift_card_id) && $this->gift_card_amount > 0;
    }

    /**
     * Получить итоговую сумму с учетом подарочной карты
     */
    public function getTotalWithGiftCard(): float
    {
        return max(0, $this->total_amount - $this->gift_card_amount);
    }
}
