<?php

namespace App\Policies;

use App\Models\User;
use App\Models\TaskComment;
use Illuminate\Auth\Access\HandlesAuthorization;

class TaskCommentPolicy
{
    use HandlesAuthorization;

    public function update(User $user, TaskComment $comment): bool
    {
        return $user->id === $comment->user_id || $user->type === 'admin';
    }

    public function delete(User $user, TaskComment $comment): bool
    {
        return $user->id === $comment->user_id || 
               $user->id === $comment->task->creator_id || 
               $user->type === 'admin';
    }
} 