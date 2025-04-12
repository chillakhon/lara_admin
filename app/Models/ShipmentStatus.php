<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
/**
 * @OA\Schema(
 *     schema="ShipmentStatus",
 *     type="object",
 *     required={"code", "name"},
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         description="Уникальный идентификатор статуса доставки"
 *     ),
 *     @OA\Property(
 *         property="code",
 *         type="string",
 *         description="Код статуса доставки (например, 'new', 'processing')"
 *     ),
 *     @OA\Property(
 *         property="name",
 *         type="string",
 *         description="Название статуса доставки"
 *     ),
 *     @OA\Property(
 *         property="description",
 *         type="string",
 *         description="Описание статуса доставки",
 *         nullable=true
 *     ),
 *     @OA\Property(
 *         property="created_at",
 *         type="string",
 *         format="date-time",
 *         description="Дата и время создания статуса"
 *     ),
 *     @OA\Property(
 *         property="updated_at",
 *         type="string",
 *         format="date-time",
 *         description="Дата и время последнего обновления статуса"
 *     )
 * )
 */
class ShipmentStatus extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description'
    ];

    public const NEW = 'new';
    public const PROCESSING = 'processing';
    public const READY_FOR_PICKUP = 'ready_for_pickup';
    public const IN_TRANSIT = 'in_transit';
    public const DELIVERED = 'delivered';
    public const CANCELLED = 'cancelled';
    public const RETURNED = 'returned';

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class, 'status_id');
    }
}
