<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
/**
 * @OA\Schema(
 *     schema="DeliveryZone",
 *     type="object",
 *     required={"name", "country_code", "region_code", "city_code", "postal_code_pattern", "delivery_method_id"},
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         description="Уникальный идентификатор зоны доставки"
 *     ),
 *     @OA\Property(
 *         property="name",
 *         type="string",
 *         description="Название зоны доставки"
 *     ),
 *     @OA\Property(
 *         property="country_code",
 *         type="string",
 *         description="Код страны зоны доставки"
 *     ),
 *     @OA\Property(
 *         property="region_code",
 *         type="string",
 *         description="Код региона зоны доставки"
 *     ),
 *     @OA\Property(
 *         property="city_code",
 *         type="string",
 *         description="Код города зоны доставки"
 *     ),
 *     @OA\Property(
 *         property="postal_code_pattern",
 *         type="string",
 *         description="Шаблон почтового кода для зоны доставки"
 *     ),
 *     @OA\Property(
 *         property="delivery_method_id",
 *         type="integer",
 *         description="Идентификатор метода доставки, связанного с зоной"
 *     ),
 *     @OA\Property(
 *         property="created_at",
 *         type="string",
 *         format="date-time",
 *         description="Дата и время создания зоны доставки"
 *     ),
 *     @OA\Property(
 *         property="updated_at",
 *         type="string",
 *         format="date-time",
 *         description="Дата и время последнего обновления зоны доставки"
 *     )
 * )
 */

class DeliveryZone extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'country_code',
        'region_code',
        'city_code',
        'postal_code_pattern',
        'delivery_method_id'
    ];

    public function deliveryMethod(): BelongsTo
    {
        return $this->belongsTo(DeliveryMethod::class);
    }

    public function rates(): HasMany
    {
        return $this->hasMany(DeliveryRate::class);
    }
}
