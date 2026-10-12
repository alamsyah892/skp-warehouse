<?php

namespace App\Policies;

use App\Models\Bank;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class BankPolicy
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
        return $user->can('Read Bank');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Bank $Bank): bool
    {
        return $user->can('Read Bank');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('Create Bank');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Bank $Bank): bool
    {
        return $user->can('Update Bank');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Bank $Bank): bool
    {
        return $user->can('Delete Bank');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Bank $Bank): bool
    {
        return $user->can('Delete Bank');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Bank $Bank): bool
    {
        return $user->can('Delete Bank');
    }
}
