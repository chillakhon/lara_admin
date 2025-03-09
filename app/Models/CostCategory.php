<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @OA\Schema(
 *     schema="CostCategory",
 *     type="object",
 *     required={"id", "name", "type", "is_active", "created_at", "updated_at"},
 *     @OA\Property(property="id", type="integer", description="Unique identifier of the cost category"),
 *     @OA\Property(property="name", type="string", description="Name of the cost category"),
 *     @OA\Property(property="type", type="string", description="Type of the cost category (e.g., 'Material', 'Labor', etc.)"),
 *     @OA\Property(property="description", type="string", nullable=true, description="Description of the cost category"),
 *     @OA\Property(property="is_active", type="boolean", description="Whether the cost category is active"),
 *     @OA\Property(property="created_at", type="string", format="date-time", description="Timestamp when the cost category was created"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", description="Timestamp when the cost category was last updated")
 * )
 */
class CostCategory extends Model
{
    protected $fillable = [
        'name',
        'type',
        'description',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    // Типы расходов
    public const TYPE_LABOR = 'labor';
    public const TYPE_OVERHEAD = 'overhead';
    public const TYPE_MANAGEMENT = 'management';

    // Получение доступных типов расходов
    public static function getTypes(): array
    {
        return [
            self::TYPE_LABOR => 'Оплата труда',
            self::TYPE_OVERHEAD => 'Накладные расходы',
            self::TYPE_MANAGEMENT => 'Управленческие расходы'
        ];
    }

    public function recipeCostRates(): HasMany
    {
        return $this->hasMany(RecipeCostRate::class);
    }

    public function productionBatchCosts(): HasMany
    {
        return $this->hasMany(ProductionBatchCost::class);
    }
}
