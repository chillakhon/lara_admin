<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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