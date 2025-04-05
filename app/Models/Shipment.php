<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
/**
 * @OA\Schema(
 *     schema="Shipment",
 *     type="object",
 *     required={"order_id", "delivery_method_id", "status_id", "tracking_number", "shipping_date", "recipient_name", "recipient_phone", "weight", "cost"},
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         description="Уникальный идентификатор отправления"
 *     ),
 *     @OA\Property(
 *         property="order_id",
 *         type="integer",
 *         description="Идентификатор заказа, с которым связано отправление"
 *     ),
 *     @OA\Property(
 *         property="delivery_method_id",
 *         type="integer",
 *         description="Идентификатор метода доставки"
 *     ),
 *     @OA\Property(
 *         property="status_id",
 *         type="integer",
 *         description="Идентификатор статуса отправления"
 *     ),
 *     @OA\Property(
 *         property="tracking_number",
 *         type="string",
 *         description="Номер отслеживания отправления"
 *     ),
 *     @OA\Property(
 *         property="provider_data",
 *         type="object",
 *         description="Данные провайдера доставки",
 *         additionalProperties=true
 *     ),
 *     @OA\Property(
 *         property="shipping_date",
 *         type="string",
 *         format="date-time",
 *         description="Дата отправки"
 *     ),
 *     @OA\Property(
 *         property="estimated_delivery_date",
 *         type="string",
 *         format="date-time",
 *         description="Предполагаемая дата доставки"
 *     ),
 *     @OA\Property(
 *         property="actual_delivery_date",
 *         type="string",
 *         format="date-time",
 *         description="Фактическая дата доставки"
 *     ),
 *     @OA\Property(
 *         property="shipping_address",
 *         type="string",
 *         description="Адрес отправки"
 *     ),
 *     @OA\Property(
 *         property="recipient_name",
 *         type="string",
 *         description="Имя получателя"
 *     ),
 *     @OA\Property(
 *         property="recipient_phone",
 *         type="string",
 *         description="Телефон получателя"
 *     ),
 *     @OA\Property(
 *         property="weight",
 *         type="number",
 *         format="float",
 *         description="Вес отправления"
 *     ),
 *     @OA\Property(
 *         property="dimensions",
 *         type="object",
 *         description="Габариты отправления",
 *         additionalProperties=true
 *     ),
 *     @OA\Property(
 *         property="cost",
 *         type="number",
 *         format="float",
 *         description="Стоимость отправления"
 *     ),
 *     @OA\Property(
 *         property="notes",
 *         type="string",
 *         description="Примечания к отправлению"
 *     ),
 *     @OA\Property(
 *         property="created_at",
 *         type="string",
 *         format="date-time",
 *         description="Дата и время создания отправления"
 *     ),
 *     @OA\Property(
 *         property="updated_at",
 *         type="string",
 *         format="date-time",
 *         description="Дата и время последнего обновления отправления"
 *     )
 * )
 */

class Shipment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'order_id',
        'delivery_method_id',
        'status_id',
        'tracking_number',
        'provider_data',
        'shipping_date',
        'estimated_delivery_date',
        'actual_delivery_date',
        'shipping_address',
        'recipient_name',
        'recipient_phone',
        'weight',
        'dimensions',
        'cost',
        'notes'
    ];

    protected $casts = [
        'provider_data' => 'array',
        'dimensions' => 'array',
        'shipping_date' => 'datetime',
        'estimated_delivery_date' => 'datetime',
        'actual_delivery_date' => 'datetime',
        'weight' => 'decimal:3',
        'cost' => 'decimal:2'
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function deliveryMethod(): BelongsTo
    {
        return $this->belongsTo(DeliveryMethod::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(ShipmentStatus::class, 'status_id');
    }
}
