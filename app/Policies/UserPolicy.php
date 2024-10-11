<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user)
    {
        return $user->hasRole('admin');
    }

    public function create(User $user)
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, User $model)
    {
        return $user->hasRole('admin');
    }

    public function delete(User $user, User $model)
    {
        return $user->hasRole('admin');
    }
}
