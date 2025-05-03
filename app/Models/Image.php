<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @OA\Schema(
 *     schema="Image",
 *     type="object",
 *     title="Image",
 *     required={"path", "url", "is_main", "order"},
 *
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         example=1
 *     ),
 *     @OA\Property(
 *         property="path",
 *         type="string",
 *         example="product_images/1/abc123.jpg",
 *         description="Относительный путь к изображению на сервере"
 *     ),
 *     @OA\Property(
 *         property="url",
 *         type="string",
 *         example="https://example.com/storage/product_images/1/abc123.jpg",
 *         description="Полный URL для доступа к изображению"
 *     ),
 *     @OA\Property(
 *         property="is_main",
 *         type="boolean",
 *         example=true,
 *         description="Флаг, указывающий, является ли изображение основным"
 *     ),
 *     @OA\Property(
 *         property="order",
 *         type="integer",
 *         example=1,
 *         description="Порядок сортировки изображения"
 *     )
 * )
 */
class Image extends Model
{
    use HasFactory;

    public $timestamps = false;

    // protected $fillable = [
    //     'path',
    //     'url',
    //     'is_main',
    //     'order'
    // ];

    protected $guarded = ['id'];

    // public function imageable(): MorphTo
    // {
    //     return $this->morphTo();
    // }

    public function item()
    {
        return $this->morphTo('item', 'item_type', 'item_id');
    }

    // public function optionValues()
    // {
    //     return $this->morphedByMany(OptionValue::class, 'imageable')->withTimestamps();
    // }

    // public function products()
    // {
    //     return $this->morphedByMany(Product::class, 'imageable')->withTimestamps();
    // }

}
