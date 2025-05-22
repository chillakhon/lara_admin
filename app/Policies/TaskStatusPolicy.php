<?php

namespace App\Policies;

use App\Models\User;

class TaskStatusPolicy
{
    /**
     * Create a new policy instance.
     */

    public function viewAny(User $user): bool
    {
        return true;
    }
}
