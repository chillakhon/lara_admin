<?php

namespace App\Policies;

use App\Models\ColorOptionValue;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ColorOptionValuePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        //
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ColorOptionValue $colorOptionValue): bool
    {
        //
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        //
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ColorOptionValue $colorOptionValue): bool
    {
        //
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ColorOptionValue $colorOptionValue): bool
    {
        //
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ColorOptionValue $colorOptionValue): bool
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ColorOptionValue $colorOptionValue): bool
    {
        //
    }
}
