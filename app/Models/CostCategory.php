<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
