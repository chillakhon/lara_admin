<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @OA\Schema(
 *     schema="TaskPriority",
 *     type="object",
 *     description="Приоритет задачи",
 *     @OA\Property(property="id", type="integer", format="int64", example=1),
 *     @OA\Property(property="name", type="string", example="Высокий"),
 *     @OA\Property(property="slug", type="string", example="high"),
 *     @OA\Property(
 *         property="color",
 *         type="string",
 *         example="#6B7280",
 *         description="HEX-код цвета приоритета"
 *     ),
 *     @OA\Property(
 *         property="level",
 *         type="integer",
 *         example=3,
 *         description="Уровень приоритета (чем выше число - тем выше приоритет)"
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
class TaskPriority extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'color',
        'level'
    ];

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'priority_id');
    }
}
