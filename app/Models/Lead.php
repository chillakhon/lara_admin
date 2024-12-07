<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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

    const STATUS_NEW = 'new';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_REJECTED = 'rejected';

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

    public static function getStatuses(): array
    {
        return [
            self::STATUS_NEW => 'Новая',
            self::STATUS_PROCESSING => 'В обработке',
            self::STATUS_COMPLETED => 'Завершена',
            self::STATUS_REJECTED => 'Отклонена',
        ];
    }
} 