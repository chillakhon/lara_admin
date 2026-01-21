<?php

namespace App\Models\Oto;

use App\Models\Client;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OtoBannerView extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'oto_banner_id',
        'client_id',
        'ip_address',
        'user_agent',
        'session_id',
        'viewed_at',
    ];

    protected $casts = [
        'viewed_at' => 'datetime',
    ];

    /**
     * Баннер
     */
    public function banner(): BelongsTo
    {
        return $this->belongsTo(OtoBanner::class, 'oto_banner_id');
    }

    /**
     * Клиент (если авторизован)
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
