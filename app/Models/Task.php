<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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