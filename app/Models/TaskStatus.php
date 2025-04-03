<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @OA\Schema(
 *     schema="TaskStatus",
 *     type="object",
 *     description="Статус задачи",
 *     @OA\Property(property="id", type="integer", format="int64", example=1),
 *     @OA\Property(property="name", type="string", example="В работе"),
 *     @OA\Property(property="slug", type="string", example="in-progress"),
 *     @OA\Property(
 *         property="color",
 *         type="string",
 *         example="#6B7280",
 *         description="HEX-код цвета статуса"
 *     ),
 *     @OA\Property(
 *         property="order",
 *         type="integer",
 *         example=2,
 *         description="Порядок сортировки"
 *     ),
 *     @OA\Property(
 *         property="is_default",
 *         type="boolean",
 *         example=false,
 *         description="Является ли статусом по умолчанию"
 *     ),
 *     @OA\Property(
 *         property="created_at",
 *         type="string",
 *         format="date-time",
 *         example="2024-01-01T00:00:00Z"
 *     ),
 *     @OA\Property(
 *         property="updated_at",
 *         type="string",
 *         format="date-time",
 *         example="2024-01-02T12:34:56Z"
 *     )
 * )
 */
class TaskStatus extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'color',
        'order',
        'is_default'
    ];

    protected $casts = [
        'is_default' => 'boolean'
    ];

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'status_id');
    }
}
