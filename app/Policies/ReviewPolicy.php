<?php

namespace App\Policies;

use App\Models\Review;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ReviewPolicy
{
    use HandlesAuthorization;

    public function publish(User $user, Review $review)
    {
        return $user->hasRole('admin'); // Только администраторы могут публиковать
    }

    public function unpublish(User $user, Review $review)
    {
        return $user->hasRole('admin');
    }

    public function delete(User $user, Review $review)
    {
        return $user->hasRole('admin');
    }
}
