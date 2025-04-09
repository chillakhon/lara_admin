<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @OA\Schema(
 *     schema="Task",
 *     type="object",
 *     @OA\Property(property="id", type="integer", format="int64", example=1),
 *     @OA\Property(property="title", type="string", example="Исправить баг в API"),
 *     @OA\Property(property="description", type="string", nullable=true, example="Не работает эндпоинт /tasks/create"),
 *     @OA\Property(
 *         property="status",
 *         ref="#/components/schemas/TaskStatus"
 *     ),
 *     @OA\Property(
 *         property="priority",
 *         ref="#/components/schemas/TaskPriority"
 *     ),
 *     @OA\Property(
 *         property="creator",
 *         ref="#/components/schemas/User"
 *     ),
 *     @OA\Property(
 *         property="assignee",
 *         ref="#/components/schemas/User",
 *         nullable=true
 *     ),
 *     @OA\Property(property="parent_id", type="integer", format="int64", nullable=true),
 *     @OA\Property(property="due_date", type="string", format="date-time", nullable=true, example="2024-12-31T23:59:59Z"),
 *     @OA\Property(property="started_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="completed_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="estimated_time", type="integer", example=120),
 *     @OA\Property(property="spent_time", type="integer", example=0),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-01T00:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time"),
 *     @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(
 *         property="labels",
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/TaskLabel")
 *     ),
 *     @OA\Property(
 *         property="comments",
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/TaskComment")
 *     )
 * )
 */
class Task extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'status_id',
        'priority_id',
        'creator_id',
        'assignee_id',
        'parent_id',
        'due_date',
        'started_at',
        'completed_at',
        'estimated_time',
        'spent_time',
    ];

    protected $casts = [
        'due_date' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    // Отношения
    public function status(): BelongsTo
    {
        return $this->belongsTo(TaskStatus::class);
    }

    public function priority(): BelongsTo
    {
        return $this->belongsTo(TaskPriority::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'parent_id');
    }

    public function subtasks(): HasMany
    {
        return $this->hasMany(Task::class, 'parent_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TaskComment::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TaskAttachment::class);
    }

    public function history(): HasMany
    {
        return $this->hasMany(TaskHistory::class);
    }

    public function labels(): BelongsToMany
    {
        return $this->belongsToMany(TaskLabel::class, 'task_label', 'task_id', 'label_id');
    }

    // Вспомогательные методы
    public function isOverdue(): bool
    {
        return $this->due_date && $this->due_date->isPast() && !$this->completed_at;
    }

    public function isCompleted(): bool
    {
        return (bool) $this->completed_at;
    }

    public function complete(): void
    {
        $this->update([
            'completed_at' => now(),
            'status_id' => TaskStatus::where('slug', 'completed')->first()->id
        ]);
    }
}
