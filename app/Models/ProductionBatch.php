<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @OA\Schema(
 *     schema="ProductionBatch",
 *     type="object",
 *     required={"id", "batch_number", "recipe_id", "product_variant_id", "planned_quantity", "status", "created_at", "updated_at"},
 *     @OA\Property(property="id", type="integer", description="Unique identifier for the production batch"),
 *     @OA\Property(property="batch_number", type="string", description="The batch number"),
 *     @OA\Property(property="recipe_id", type="integer", description="ID of the associated recipe"),
 *     @OA\Property(property="product_variant_id", type="integer", description="ID of the associated product variant"),
 *     @OA\Property(property="planned_quantity", type="number", format="float", description="The planned quantity for the production batch"),
 *     @OA\Property(property="actual_quantity", type="number", format="float", nullable=true, description="The actual quantity produced"),
 *     @OA\Property(property="status", type="string", enum={"planned", "pending", "in_progress", "completed", "cancelled", "failed"}, description="The status of the production batch"),
 *     @OA\Property(property="unit_cost", type="number", format="float", nullable=true, description="The unit cost of production"),
 *     @OA\Property(property="total_material_cost", type="number", format="float", nullable=true, description="The total cost of materials used in the production batch"),
 *     @OA\Property(property="additional_costs", type="number", format="float", description="Any additional costs for the production batch"),
 *     @OA\Property(property="planned_start_date", type="string", format="date-time", nullable=true, description="Planned start date for the production batch"),
 *     @OA\Property(property="planned_end_date", type="string", format="date-time", nullable=true, description="Planned end date for the production batch"),
 *     @OA\Property(property="started_at", type="string", format="date-time", nullable=true, description="Actual start date of the production batch"),
 *     @OA\Property(property="completed_at", type="string", format="date-time", nullable=true, description="Actual completion date of the production batch"),
 *     @OA\Property(property="created_by", type="integer", description="ID of the user who created the production batch"),
 *     @OA\Property(property="completed_by", type="integer", nullable=true, description="ID of the user who completed the production batch"),
 *     @OA\Property(property="notes", type="string", nullable=true, description="Additional notes for the production batch"),
 *     @OA\Property(property="created_at", type="string", format="date-time", description="Timestamp when the production batch was created"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", description="Timestamp when the production batch was last updated"),
 *     @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, description="Timestamp when the production batch was deleted")
 * )
 */
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

    public function materialConsumptions(): HasMany
    {
        return $this->hasMany(ComponentConsumption::class, 'production_batch_id');
    }
}
