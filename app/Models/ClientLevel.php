<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @OA\Schema(
 *     schema="ClientLevel",
 *     type="object",
 *     required={"name", "threshold", "calculation_type", "discount_percentage"},
 *     @OA\Property(property="id", type="integer", description="ID of the client level"),
 *     @OA\Property(property="name", type="string", description="Name of the client level"),
 *     @OA\Property(property="threshold", type="number", format="float", description="Threshold for the client level"),
 *     @OA\Property(property="calculation_type", type="string", enum={"order_count", "order_sum"}, description="Calculation type for the client level"),
 *     @OA\Property(property="discount_percentage", type="number", format="float", description="Discount percentage for the client level")
 * )
 */


class ClientLevel extends Model
{
    use HasFactory;

    protected $guarded = false;

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
    }
}
