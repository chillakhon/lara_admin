<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
/**
 * @OA\Schema(
 *     schema="DeliveryRate",
 *     type="object",
 *     required={"delivery_method_id", "delivery_zone_id", "min_weight", "max_weight", "min_total", "max_total", "price"},
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         description="Unique identifier for the delivery rate"
 *     ),
 *     @OA\Property(
 *         property="delivery_method_id",
 *         type="integer",
 *         description="The ID of the related delivery method"
 *     ),
 *     @OA\Property(
 *         property="delivery_zone_id",
 *         type="integer",
 *         description="The ID of the related delivery zone"
 *     ),
 *     @OA\Property(
 *         property="min_weight",
 *         type="number",
 *         format="float",
 *         description="The minimum weight for this rate"
 *     ),
 *     @OA\Property(
 *         property="max_weight",
 *         type="number",
 *         format="float",
 *         description="The maximum weight for this rate"
 *     ),
 *     @OA\Property(
 *         property="min_total",
 *         type="number",
 *         format="float",
 *         description="The minimum total order value for this rate"
 *     ),
 *     @OA\Property(
 *         property="max_total",
 *         type="number",
 *         format="float",
 *         description="The maximum total order value for this rate"
 *     ),
 *     @OA\Property(
 *         property="price",
 *         type="number",
 *         format="float",
 *         description="The price for the delivery rate"
 *     ),
 *     @OA\Property(
 *         property="estimated_days",
 *         type="integer",
 *         description="Estimated delivery days for this rate"
 *     ),
 *     @OA\Property(
 *         property="created_at",
 *         type="string",
 *         format="date-time",
 *         description="The date and time when the delivery rate was created"
 *     ),
 *     @OA\Property(
 *         property="updated_at",
 *         type="string",
 *         format="date-time",
 *         description="The date and time when the delivery rate was last updated"
 *     )
 * )
 */
class DeliveryRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'delivery_method_id',
        'delivery_zone_id',
        'min_weight',
        'max_weight',
        'min_total',
        'max_total',
        'price',
        'estimated_days'
    ];

    protected $casts = [
        'min_weight' => 'decimal:3',
        'max_weight' => 'decimal:3',
        'min_total' => 'decimal:2',
        'max_total' => 'decimal:2',
        'price' => 'decimal:2'
    ];

    public function deliveryMethod(): BelongsTo
    {
        return $this->belongsTo(DeliveryMethod::class);
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(DeliveryZone::class, 'delivery_zone_id');
    }
}
