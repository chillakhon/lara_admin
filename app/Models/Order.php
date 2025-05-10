<?php

namespace App\Models;

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

    const STATUS_NEW = 'new';
    const STATUS_PROCESSING = 'processing';
    const STATUS_APPROVED = 'approved';
    const STATUS_SHIPPED = 'shipped';
    const STATUS_COMPLETED = 'completed';
    const STATUS_RETURN_IN_PROGRESS = 'return_in_progress';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_RETURNED = 'returned';

    const STATUSES = [
        ['value' => self::STATUS_NEW, 'label' => 'Новый'],
        ['value' => self::STATUS_PROCESSING, 'label' => 'В обработке'],
        ['value' => self::STATUS_APPROVED, 'label' => 'Согласован'],
        ['value' => self::STATUS_SHIPPED, 'label' => 'Отгружен'],
        ['value' => self::STATUS_COMPLETED, 'label' => 'Доставлен'],
        ['value' => self::STATUS_RETURN_IN_PROGRESS, 'label' => 'В процессе возврата'],
        ['value' => self::STATUS_CANCELLED, 'label' => 'Отменен'],
        ['value' => self::STATUS_RETURNED, 'label' => 'Возврат'],
    ];

    const PAYMENT_STATUS_PENDING = 'pending';
    const PAYMENT_STATUS_PAID = 'paid';
    const PAYMENT_STATUS_FAILED = 'failed';
    const PAYMENT_STATUS_REFUNDED = 'refunded';

    const PAYMENT_STATUSES = [
        ['value' => 'pending', 'label' => 'Ожидает оплаты'],
        ['value' => 'paid', 'label' => 'Оплачен'],
        ['value' => 'failed', 'label' => 'Ошибка оплаты'],
        ['value' => 'refunded', 'label' => 'Возврат'],
    ];

    // protected $fillable = [
    //     'order_number',
    //     'client_id',
    //     'lead_id',
    //     'status',
    //     'payment_status',
    //     'total_amount',
    //     'discount_amount',
    //     'promo_code_id',
    //     'payment_method',
    //     'payment_provider',
    //     'payment_id',
    //     'paid_at',
    //     'source',
    //     'utm_source',
    //     'utm_medium',
    //     'utm_campaign',
    //     'utm_content',
    //     'utm_term',
    //     'ip_address',
    //     'user_agent',
    //     'notes',
    //     'delivery_method_id',
    //     'delivery_date',
    // ];

    protected $guarded = ['id'];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'delivery_date' => 'datetime',
    ];

    public function deliveryTarget()
    {
        return $this->belongsTo(DeliveryTarget::class, 'delivery_target_id');
    }

    public function deliveryMethod(): BelongsTo
    {
        return $this->belongsTo(DeliveryMethod::class, 'delivery_method_id');
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

    public static function getStatuses(): array
    {
        return [
            self::STATUS_NEW => 'Новый',
            self::STATUS_PROCESSING => 'В обработке',
            self::STATUS_APPROVED => 'Согласован', // Добавлено
            self::STATUS_SHIPPED => 'Отгружен',   // Добавлено
            self::STATUS_COMPLETED => 'Доставлен', // Обновлено (было "Завершен")
            self::STATUS_RETURN_IN_PROGRESS => 'В процессе возврата', // Добавлено
            self::STATUS_CANCELLED => 'Отменен',
            self::STATUS_RETURNED => 'Возврат', // Добавлено
        ];
    }
    public static function getPaymentStatuses(): array
    {
        return [
            self::PAYMENT_STATUS_PENDING => 'Ожидает оплаты',
            self::PAYMENT_STATUS_PAID => 'Оплачен',
            self::PAYMENT_STATUS_FAILED => 'Ошибка оплаты',
            self::PAYMENT_STATUS_REFUNDED => 'Возврат',
        ];
    }

    public function updatePaymentStatus(string $status, ?string $paymentId = null)
    {
        $this->payment_status = $status;
        if ($status === self::PAYMENT_STATUS_PAID) {
            $this->paid_at = now();
            $this->payment_id = $paymentId;
        }
        $this->save();

        // Записываем в историю
        $this->history()->create([
            'payment_status' => $status,
            'comment' => "Статус оплаты изменен на: {$this->getPaymentStatuses()[$status]}",
            'user_id' => auth()->id()
        ]);
    }

    public function isPaid(): bool
    {
        return $this->payment_status === self::PAYMENT_STATUS_PAID;
    }

    public function canBePaid(): bool
    {
        return in_array($this->payment_status, [
            self::PAYMENT_STATUS_PENDING,
            self::PAYMENT_STATUS_FAILED
        ]);
    }


    public function updateTotalAmount(): void
    {
        $this->total_amount = $this->items()->sum(DB::raw('quantity * price'));
        $this->save();
    }

}

