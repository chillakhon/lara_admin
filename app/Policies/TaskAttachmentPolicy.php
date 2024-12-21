<?php

namespace App\Policies;

use App\Models\User;
use App\Models\TaskAttachment;
use Illuminate\Auth\Access\HandlesAuthorization;

class TaskAttachmentPolicy
{
    use HandlesAuthorization;

    public function delete(User $user, TaskAttachment $attachment): bool
    {
        return $user->id === $attachment->user_id || 
               $user->id === $attachment->task->creator_id || 
               $user->type === 'admin';
    }
} 