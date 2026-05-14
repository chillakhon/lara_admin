<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @OA\Schema(
 *     schema="TaskComment",
 *     type="object",
 *     description="Комментарий к задаче",
 *     @OA\Property(property="id", type="integer", format="int64", example=1),
 *     @OA\Property(
 *         property="task_id",
 *         type="integer",
 *         format="int64",
 *         example=123,
 *         description="ID связанной задачи"
 *     ),
 *     @OA\Property(
 *         property="user_id",
 *         type="integer",
 *         format="int64",
 *         example=456,
 *         description="ID автора комментария"
 *     ),
 *     @OA\Property(
 *         property="content",
 *         type="string",
 *         example="Этот баг нужно исправить в первую очередь!",
 *         description="Текст комментария"
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
 *     ),
 *     @OA\Property(
 *         property="deleted_at",
 *         type="string",
 *         format="date-time",
 *         nullable=true,
 *         description="Дата мягкого удаления"
 *     ),
 *     @OA\Property(
 *         property="user",
 *         ref="#/components/schemas/User",
 *         description="Данные автора комментария"
 *     )
 * )
 */
class TaskComment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'task_id',
        'user_id',
        'content'
    ];

    protected $with = ['user'];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->select(['id', 'name', 'email']);
    }
}
