<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductionBatch extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'batch_number',
        'recipe_id',
        'product_variant_id',
        'planned_quantity',
        'actual_quantity',
        'status',
        'unit_cost',
        'total_material_cost',
        'additional_costs',
        'planned_start_date',
        'planned_end_date',
        'started_at',
        'completed_at',
        'created_by',
        'completed_by',
        'notes'
    ];

    protected $casts = [
        'planned_quantity' => 'decimal:3',
        'actual_quantity' => 'decimal:3',
        'unit_cost' => 'decimal:2',
        'total_material_cost' => 'decimal:2',
        'additional_costs' => 'decimal:2',
        'planned_start_date' => 'datetime',
        'planned_end_date' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime'
    ];

    public function recipe()
    {
        return $this->belongsTo(Recipe::class);
    }

    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function componentConsumptions()
    {
        return $this->hasMany(ComponentConsumption::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function completedBy()
    {
        return $this->belongsTo(User::class, 'completed_by');
    }
}
