<?php

namespace App\Policies;

use App\Models\Currency;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class CurrencyPolicy
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
        return $user->can('Read Currency');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Currency $Currency): bool
    {
        return $user->can('Read Currency');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('Create Currency');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Currency $Currency): bool
    {
        return $user->can('Update Currency');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Currency $Currency): bool
    {
        return $user->can('Delete Currency');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Currency $Currency): bool
    {
        return $user->can('Delete Currency');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Currency $Currency): bool
    {
        return $user->can('Delete Currency');
    }
}
