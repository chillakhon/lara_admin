<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @OA\Schema(
 *     schema="TaskHistory",
 *     type="object",
 *     description="История изменений задачи",
 *     @OA\Property(property="id", type="integer", format="int64", example=1),
 *     @OA\Property(
 *         property="task_id",
 *         type="integer",
 *         format="int64",
 *         example=123,
 *         description="ID задачи"
 *     ),
 *     @OA\Property(
 *         property="user_id",
 *         type="integer",
 *         format="int64",
 *         example=456,
 *         description="ID пользователя, внесшего изменение"
 *     ),
 *     @OA\Property(
 *         property="field",
 *         type="string",
 *         example="status",
 *         description="Название измененного поля"
 *     ),
 *     @OA\Property(
 *         property="old_value",
 *         type="string",
 *         nullable=true,
 *         example="pending",
 *         description="Старое значение поля"
 *     ),
 *     @OA\Property(
 *         property="new_value",
 *         type="string",
 *         nullable=true,
 *         example="completed",
 *         description="Новое значение поля"
 *     ),
 *     @OA\Property(
 *         property="created_at",
 *         type="string",
 *         format="date-time",
 *         example="2024-01-01T12:00:00Z"
 *     ),
 *     @OA\Property(
 *         property="updated_at",
 *         type="string",
 *         format="date-time",
 *         example="2024-01-01T12:00:00Z"
 *     )
 * )
 */
class TaskHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id',
        'user_id',
        'field',
        'old_value',
        'new_value'
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
