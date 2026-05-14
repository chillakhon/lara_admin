<?php

namespace App\Models\Segments;

use App\Models\Client;
use App\Models\PromoCode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Segment extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'conditions',
        'is_active',
        'recalculate_frequency',
        'last_recalculated_at',
    ];

    protected $casts = [
        'conditions' => 'array',
        'is_active' => 'boolean',
        'last_recalculated_at' => 'datetime',
    ];

    /**
     * Клиенты, входящие в сегмент
     */
    public function clients(): BelongsToMany
    {
        return $this->belongsToMany(Client::class, 'client_segment')
            ->withPivot('added_at')
            ->withTimestamps();
    }

    /**
     * Промокоды, привязанные к сегменту
     */
    public function promoCodes(): BelongsToMany
    {
        return $this->belongsToMany(PromoCode::class, 'promo_code_segment')
            ->withPivot('auto_apply')
            ->withTimestamps();
    }

    /**
     * Scope для активных сегментов
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope для сегментов, требующих пересчёта
     */
    public function scopeNeedsRecalculation($query)
    {
        return $query->where('recalculate_frequency', 'on_view')
            ->where(function ($q) {
                $q->whereNull('last_recalculated_at')
                    ->orWhere('last_recalculated_at', '<', now()->subMinutes(5));
            });
    }

    /**
     * Получить количество клиентов в сегменте
     */
    public function getClientsCountAttribute(): int
    {
        return $this->clients()->count();
    }

    /**
     * Проверить, нужен ли пересчёт сегмента
     */
    public function needsRecalculation(): bool
    {
        if ($this->recalculate_frequency === 'manual') {
            return false;
        }

        // Пересчитываем, если прошло больше 5 минут с последнего пересчёта
        return $this->last_recalculated_at === null
            || $this->last_recalculated_at->lt(now()->subMinutes(5));
    }

    /**
     * Отметить, что сегмент был пересчитан
     */
    public function markAsRecalculated(): void
    {
        $this->update(['last_recalculated_at' => now()]);
    }
}
