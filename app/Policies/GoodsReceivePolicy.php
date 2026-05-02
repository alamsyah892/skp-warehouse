<?php

namespace App\Policies;

use App\Enums\GoodsReceiveStatus;
use App\Models\GoodsReceive;
use App\Models\User;

class GoodsReceivePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('Read Goods Receipt');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, GoodsReceive $goodsReceive): bool
    {
        return $user->can('Read Goods Receipt');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('Create Goods Receipt');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, GoodsReceive $goodsReceive): bool
    {
        return $user->can('Update Goods Receipt')
            && $goodsReceive->status !== GoodsReceiveStatus::CANCELED
            && $goodsReceive->status !== GoodsReceiveStatus::CONFIRMED;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, GoodsReceive $goodsReceive): bool
    {
        return $user->can('Delete Goods Receipt')
            && $goodsReceive->status !== GoodsReceiveStatus::CANCELED
            && $goodsReceive->status !== GoodsReceiveStatus::CONFIRMED;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, GoodsReceive $goodsReceive): bool
    {
        return $user->can('Delete Goods Receipt');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, GoodsReceive $goodsReceive): bool
    {
        return $user->can('Delete Goods Receipt');
    }
}
