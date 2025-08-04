<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class Lead extends Model
{
    protected $fillable = [
        'lead_type_id',
        'client_id',
        'status',
        'data',
        'source',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_content',
        'utm_term',
        'ip_address',
        'user_agent'
    ];

    protected $casts = [
        'data' => 'array'
    ];

    // === Константы статусов ===
    public const STATUS_NEW = 'new';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_READY_TO_SHIP = 'ready_to_ship';
    public const STATUS_ASSEMBLING = 'assembling';
    public const STATUS_SHIPPED = 'shipped';
    public const STATUS_IN_TRANSIT = 'in_transit';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_RECEIVED = 'received';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_RETURN_IN_PROGRESS = 'return_in_progress';
    public const STATUS_RETURNED = 'returned';

    // === Связи, аксессоры и т.п. ===

    public function type()
    {
        return $this->belongsTo(LeadType::class, 'lead_type_id');
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function history()
    {
        return $this->hasMany(LeadHistory::class);
    }

    public function getPhoneAttribute()
    {
        return $this->data['phone'] ?? null;
    }

    public function getEmailAttribute()
    {
        return $this->data['email'] ?? null;
    }

    public function getNameAttribute()
    {
        return $this->data['name'] ?? null;
    }

    // === Полный список статусов для фронта (value, label, color) ===
    public static function getStatuses(): array
    {
        return [
            self::STATUS_NEW => [
                'value' => self::STATUS_NEW,
                'label' => 'Новая',
                'color' => '#ef0000',
            ],
            self::STATUS_PROCESSING => [
                'value' => self::STATUS_PROCESSING,
                'label' => 'В обработке',
                'color' => '#ffad00',
            ],
            self::STATUS_COMPLETED => [
                'value' => self::STATUS_COMPLETED,
                'label' => 'Завершена',
                'color' => '#00ff21',
            ],
            self::STATUS_REJECTED => [
                'value' => self::STATUS_REJECTED,
                'label' => 'Отклонена',
                'color' => '#B22222',
            ],
            self::STATUS_READY_TO_SHIP => [
                'value' => self::STATUS_READY_TO_SHIP,
                'label' => 'Готов к отправке',
                'color' => '#0014fd',
            ],
            self::STATUS_ASSEMBLING => [
                'value' => self::STATUS_ASSEMBLING,
                'label' => 'На сборке',
                'color' => '#FFD700',
            ],
            self::STATUS_SHIPPED => [
                'value' => self::STATUS_SHIPPED,
                'label' => 'Отправка',
                'color' => '#4169E1',
            ],
            self::STATUS_IN_TRANSIT => [
                'value' => self::STATUS_IN_TRANSIT,
                'label' => 'В пути',
                'color' => '#00c8cf',
            ],
            self::STATUS_DELIVERED => [
                'value' => self::STATUS_DELIVERED,
                'label' => 'Доставлен',
                'color' => '#32CD32',
            ],
            self::STATUS_RECEIVED => [
                'value' => self::STATUS_RECEIVED,
                'label' => 'Получен',
                'color' => '#228B22',
            ],
            self::STATUS_CANCELLED => [
                'value' => self::STATUS_CANCELLED,
                'label' => 'Отменён',
                'color' => '#B22222',
            ],
            self::STATUS_RETURN_IN_PROGRESS => [
                'value' => self::STATUS_RETURN_IN_PROGRESS,
                'label' => 'В процессе возврата',
                'color' => '#FF69B4',
            ],
            self::STATUS_RETURNED => [
                'value' => self::STATUS_RETURNED,
                'label' => 'Возвращён',
                'color' => '#A9A9A9',
            ],
        ];
    }

    // === Для валидации: просто массив строковых значений ===
    public static function getStatusValues(): array
    {
        return array_keys(self::getStatuses());
    }


    public static function getPaymentStatusValues(): array
    {
        return [
            'pending', 'paid', 'failed', 'refunded'
        ];
    }


}
