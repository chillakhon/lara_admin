<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @OA\Schema(
 *     schema="TaskLabel",
 *     type="object",
 *     description="Метка задачи",
 *     @OA\Property(property="id", type="integer", format="int64", example=1),
 *     @OA\Property(property="name", type="string", example="Баг"),
 *     @OA\Property(property="slug", type="string", example="bug"),
 *     @OA\Property(
 *         property="color",
 *         type="string",
 *         example="#6B7280",
 *         description="HEX-код цвета метки"
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
class TaskLabel extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'color'
    ];

    public function tasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'task_label', 'label_id', 'task_id');
    }
}
