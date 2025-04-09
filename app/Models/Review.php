<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @OA\Schema(
 *     schema="Review",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=14),
 *     @OA\Property(property="content", type="string", example="Отличный продукт!"),
 *     @OA\Property(property="rating", type="integer", example=5),
 *     @OA\Property(property="is_verified", type="boolean", example=true),
 *     @OA\Property(property="is_published", type="boolean", example=true),
 *     @OA\Property(property="published_at", type="string", format="date-time", example="09.04.2025 12:00", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="09.04.2025 11:22"),
 *     @OA\Property(
 *         property="status",
 *         type="string",
 *         enum={"new", "published"},
 *         example="published",
 *         description="Статус отзыва: 'new' (новый) или 'published' (опубликован)"
 *     ),
 *     @OA\Property(
 *         property="client",
 *         type="object",
 *         nullable=true,
 *         @OA\Property(property="id", type="integer", example=11),
 *         @OA\Property(property="name", type="string", example="Super Admin"),
 *         @OA\Property(property="email", type="string", example="superadmin@example.com"),
 *         @OA\Property(property="avatar", type="string", example=null, nullable=true)
 *     ),
 *     @OA\Property(
 *         property="reviewable",
 *         type="object",
 *         nullable=true,
 *         @OA\Property(property="id", type="integer", example=1),
 *         @OA\Property(property="type", type="string", example="Product"),
 *         @OA\Property(property="name", type="string", example="Product Name")
 *     ),
 *     @OA\Property(
 *         property="attributes",
 *         type="array",
 *         @OA\Items(
 *             type="object",
 *             @OA\Property(property="id", type="integer", example=23),
 *             @OA\Property(property="name", type="string", example="Качество"),
 *             @OA\Property(property="rating", type="integer", example=5)
 *         )
 *     ),
 *     @OA\Property(
 *         property="responses",
 *         type="array",
 *         @OA\Items(
 *             type="object",
 *             @OA\Property(property="id", type="integer", example=1),
 *             @OA\Property(property="content", type="string", example="Спасибо за отзыв!"),
 *             @OA\Property(property="created_at", type="string", format="date-time", example="09.04.2025 12:30")
 *         )
 *     ),
 *     @OA\Property(
 *         property="images",
 *         type="array",
 *         @OA\Items(
 *             type="object",
 *             @OA\Property(property="id", type="integer", example=1),
 *             @OA\Property(property="url", type="string", example="http://127.0.0.1:8000/storage/reviews/image1.jpg"),
 *             @OA\Property(property="thumbnail", type="string", example="http://127.0.0.1:8000/storage/reviews/thumb1.jpg")
 *         )
 *     )
 * )
 */

class Review extends Model
{
    use HasFactory, SoftDeletes;

    const STATUS_NEW = 'new';
    const STATUS_PUBLISHED = 'published';

    protected $fillable = [
        'client_id',
        'reviewable_type',
        'reviewable_id',
        'content',
        'rating',
        'is_verified',
        'is_published',
        'published_at',
        'status',
    ];

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
