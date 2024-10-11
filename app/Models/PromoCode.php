<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PromoCode extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'code',
        'discount_amount',
        'discount_type',
        'starts_at',
        'expires_at',
        'max_uses',
        'times_used',
        'is_active',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function isValid()
    {
        $now = now();
        return $this->is_active &&
            $now->gte($this->starts_at) &&
            $now->lte($this->expires_at) &&
            ($this->max_uses === null || $this->times_used < $this->max_uses);
    }

    public function usages()
    {
        return $this->hasMany(PromoCodeUsage::class);
    }

    public function isValidForClient(Client $client)
    {
        if (!$this->isValid()) {
            return false;
        }

        $clientUsageCount = $this->usages()->where('client_id', $client->id)->count();

        return $clientUsageCount < $this->max_uses_per_client;
    }
}
