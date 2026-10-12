<?php

namespace App\Policies;

use App\Models\Courier;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class CourierPolicy
{
    // public function before(User $user, string $ability): ?bool
    // {
    //     if ($user->hasRole(['Project Owner'])) {
    //         return true;
    //     }

    //     return null;
    // }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('Read Courier');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Courier $Courier): bool
    {
        return $user->can('Read Courier');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('Create Courier');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Courier $Courier): bool
    {
        return $user->can('Update Courier');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Courier $Courier): bool
    {
        return $user->can('Delete Courier');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Courier $Courier): bool
    {
        return $user->can('Delete Courier');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Courier $Courier): bool
    {
        return $user->can('Delete Courier');
    }
}
