<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
/**
 * @OA\Schema(
 *     schema="DeliveryMethod",
 *     type="object",
 *     required={"name", "code", "provider_class", "settings"},
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         description="Unique identifier of the delivery method"
 *     ),
 *     @OA\Property(
 *         property="name",
 *         type="string",
 *         description="Name of the delivery method"
 *     ),
 *     @OA\Property(
 *         property="code",
 *         type="string",
 *         description="Unique code for the delivery method"
 *     ),
 *     @OA\Property(
 *         property="description",
 *         type="string",
 *         description="Description of the delivery method"
 *     ),
 *     @OA\Property(
 *         property="is_active",
 *         type="boolean",
 *         description="Whether the delivery method is active"
 *     ),
 *     @OA\Property(
 *         property="settings",
 *         type="object",
 *         description="Settings for the delivery method",
 *         additionalProperties=true
 *     ),
 *     @OA\Property(
 *         property="provider_class",
 *         type="string",
 *         description="The class name of the delivery service provider"
 *     ),
 *     @OA\Property(
 *         property="created_at",
 *         type="string",
 *         format="date-time",
 *         description="The date and time when the delivery method was created"
 *     ),
 *     @OA\Property(
 *         property="updated_at",
 *         type="string",
 *         format="date-time",
 *         description="The date and time when the delivery method was last updated"
 *     )
 * )
 */
class DeliveryMethod extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'description',
        'is_active',
        'settings',
        'provider_class'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'settings' => 'array'
    ];
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function zones(): HasMany
    {
        return $this->hasMany(DeliveryZone::class);
    }

    public function rates(): HasMany
    {
        return $this->hasMany(DeliveryRate::class);
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }

    public function getDeliveryService(): DeliveryService
    {
        $className = $this->provider_class;
        return new $className($this->settings);
    }
}
