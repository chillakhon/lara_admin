<?php

namespace App\Models\Oto;


use App\Enums\Oto\OtoBannerDeviceType;
use App\Enums\Oto\OtoBannerInputFieldType;
use App\Enums\Oto\OtoBannerStatus;
use App\Models\ContactRequest;
use App\Models\Image;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class OtoBanner extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'status',
        'device_type',
        'title',
        'subtitle',
        'button_enabled',
        'button_text',
        'input_field_enabled',
        'input_field_type',
        'input_field_label',
        'input_field_placeholder',
        'input_field_required',
        'display_delay_seconds',
        'privacy_text',
        'segment_ids',
    ];

    protected $casts = [
        'status' => OtoBannerStatus::class,
        'device_type' => OtoBannerDeviceType::class,
        'input_field_type' => OtoBannerInputFieldType::class,
        'button_enabled' => 'boolean',
        'input_field_enabled' => 'boolean',
        'input_field_required' => 'boolean',
        'display_delay_seconds' => 'integer',
        'segment_ids' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Изображения баннера (polymorphic)
     */
    public function images(): MorphMany
    {
        return $this->morphMany(Image::class, 'item', 'item_type', 'item_id');
    }

    /**
     * Главное изображение баннера
     */
    public function mainImage()
    {
        return $this->morphOne(Image::class, 'item', 'item_type', 'item_id')
            ->where('is_main', true);
    }

    /**
     * Просмотры баннера
     */
    public function views(): HasMany
    {
        return $this->hasMany(OtoBannerView::class, 'oto_banner_id');
    }

    /**
     * Заявки по баннеру
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(ContactRequest::class, 'oto_banner_id');
    }

    /**
     * Scope: только активные баннеры
     */
    public function scopeActive($query)
    {
        return $query->where('status', OtoBannerStatus::ACTIVE);
    }

    /**
     * Scope: по типу устройства
     */
    public function scopeForDevice($query, OtoBannerDeviceType $deviceType)
    {
        return $query->where('device_type', $deviceType);
    }

    /**
     * Проверка: активен ли баннер
     */
    public function isActive(): bool
    {
        return $this->status === OtoBannerStatus::ACTIVE;
    }

    /**
     * Получить количество показов
     */
    public function getViewsCountAttribute(): int
    {
        return $this->views()->count();
    }

    /**
     * Получить количество заявок
     */
    public function getSubmissionsCountAttribute(): int
    {
        return $this->submissions()->count();
    }

    /**
     * Рассчитать конверсию
     */
    public function getConversionRateAttribute(): float
    {
        $viewsCount = $this->views_count;

        if ($viewsCount === 0) {
            return 0.0;
        }

        return round(($this->submissions_count / $viewsCount) * 100, 2);
    }
}
