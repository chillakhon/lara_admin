<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class Review extends Model
{
    use HasFactory, SoftDeletes;

    const STATUS_NEW = 'new';
    const STATUS_PUBLISHED = 'published';

    // protected $fillable = [
    //     'client_id',
    //     'reviewable_type',
    //     'reviewable_id',
    //     'content',
    //     'rating',
    //     'is_verified',
    //     'is_published',
    //     'published_at',
    //     'status',
    // ];

    protected $guarded = ['id'];

    protected $casts = [
        'is_verified' => 'boolean',
        'is_published' => 'boolean',
        'published_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($review) {
            // Устанавливаем статус "Новый" при создании отзыва
            $review->status = self::STATUS_NEW;
            $review->is_published = false; // Новый отзыв не опубликован
            $review->is_verified = false;  // Новый отзыв не верифицирован
            $review->published_at = null;  // Дата публикации пока не установлена
        });

        static::updating(function ($review) {
            // Если отзыв публикуется, меняем статус на "Опубликован"
            if ($review->isDirty('is_published') && $review->is_published) {
                $review->status = self::STATUS_PUBLISHED;
                $review->published_at = $review->published_at ?? now();
            }
        });
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function reviewable(): MorphTo
    {
        return $this->morphTo();
    }

    public function responses(): HasMany
    {
        return $this->hasMany(ReviewResponse::class);
    }

    public function attributes(): HasMany
    {
        return $this->hasMany(ReviewAttribute::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ReviewImage::class)->orderBy('order');
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }


}
