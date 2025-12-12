<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Order extends Model
{
    use HasFactory, SoftDeletes;

//    protected $guarded = ['id'];

    protected $fillable = [
        'order_number',
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
    ];

    protected $casts = [
        'status' => OrderStatus::class,
        'payment_status' => PaymentStatus::class,
        'total_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'delivery_date' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function deliveryTarget()
    {
        return $this->belongsTo(DeliveryTarget::class, 'delivery_target_id');
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

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
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
            'user_id' => auth()->id()
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
            PaymentStatus::FAILED
        ]);
    }

    public function updateTotalAmount(): void
    {
        $this->total_amount = $this->items()->sum(DB::raw('quantity * price'));
        $this->save();
    }
}
