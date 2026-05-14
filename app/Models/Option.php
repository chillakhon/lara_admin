<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
/**
 * @OA\Schema(
 *     schema="Option",
 *     type="object",
 *     description="Модель опции",
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         description="ID опции",
 *         example=1
 *     ),
 *     @OA\Property(
 *         property="name",
 *         type="string",
 *         description="Название опции",
 *         example="Цвет"
 *     ),
 *     @OA\Property(
 *         property="type",
 *         type="string",
 *         description="Тип опции",
 *         example="select"
 *     ),
 *     @OA\Property(
 *         property="category_id",
 *         type="integer",
 *         description="ID категории, к которой принадлежит опция",
 *         example=1
 *     ),
 *     @OA\Property(
 *         property="is_required",
 *         type="boolean",
 *         description="Обязательная ли опция",
 *         example=true
 *     ),
 *     @OA\Property(
 *         property="order",
 *         type="integer",
 *         description="Порядок сортировки",
 *         example=0
 *     ),
 *     @OA\Property(
 *         property="created_at",
 *         type="string",
 *         format="date-time",
 *         description="Дата и время создания",
 *         example="2023-10-01T12:00:00Z"
 *     ),
 *     @OA\Property(
 *         property="updated_at",
 *         type="string",
 *         format="date-time",
 *         description="Дата и время обновления",
 *         example="2023-10-01T12:00:00Z"
 *     ),
 *     @OA\Property(
 *         property="deleted_at",
 *         type="string",
 *         format="date-time",
 *         description="Дата и время удаления (если опция удалена)",
 *         example="2023-10-01T12:00:00Z",
 *         nullable=true
 *     )
 * )
 */
class Option extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'type',
        'category_id',
        'is_required',
        'order'
    ];

    protected $casts = [
        'is_required' => 'boolean'
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function values(): HasMany
    {
        return $this->hasMany(OptionValue::class)->orderBy('order');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_options')
            ->using(ProductOption::class)
            ->withPivot(['is_required', 'order'])
            ->withTimestamps();
    }
}
