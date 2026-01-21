<?php

namespace App\Models;

use App\Models\Oto\OtoBanner;
use Illuminate\Database\Eloquent\Model;

class ContactRequest extends Model
{
    protected $fillable = [
        'client_id',
        'oto_banner_id',
        'manager_id',
        'name',
        'email',
        'phone',
        'message',
        'source',
        'status',
        'meta',
        'ip',
        'user_agent',
        'read_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'read_at' => 'datetime',
    ];


    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }


    /**
     * OTO баннер, из которого пришла заявка
     */
    public function otoBanner()
    {
        return $this->belongsTo(OtoBanner::class, 'oto_banner_id');
    }

    /**
     * Менеджер, ответственный за заявку
     */
    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    /**
     * Scope: только OTO заявки
     */
    public function scopeOtoSubmissions($query)
    {
        return $query->whereNotNull('oto_banner_id');
    }

    /**
     * Scope: по конкретному баннеру
     */
    public function scopeForBanner($query, int $bannerId)
    {
        return $query->where('oto_banner_id', $bannerId);
    }

    /**
     * Проверка: это OTO заявка?
     */
    public function isOtoSubmission(): bool
    {
        return !is_null($this->oto_banner_id);
    }


}
