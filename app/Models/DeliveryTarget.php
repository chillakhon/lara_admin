<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
/**
 * @OA\Schema(
 *     schema="DeliveryTarget",
 *     type="object",
 *     required={"name"},
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         description="Уникальный идентификатор целевого объекта доставки"
 *     ),
 *     @OA\Property(
 *         property="name",
 *         type="string",
 *         description="Название целевого объекта доставки"
 *     ),
 *     @OA\Property(
 *         property="created_at",
 *         type="string",
 *         format="date-time",
 *         description="Дата и время создания целевого объекта"
 *     ),
 *     @OA\Property(
 *         property="updated_at",
 *         type="string",
 *         format="date-time",
 *         description="Дата и время последнего обновления целевого объекта"
 *     )
 * )
 */

class DeliveryTarget extends Model
{
    use HasFactory;

    protected $fillable = [
        'name'
    ];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
