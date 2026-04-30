<?php

namespace App\Policies;

use App\Enums\GoodsIssueStatus;
use App\Models\GoodsIssue;
use App\Models\User;

class GoodsIssuePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('Read Goods Issue');
    }

    public function view(User $user, GoodsIssue $goodsIssue): bool
    {
        return $user->can('Read Goods Issue');
    }

    public function create(User $user): bool
    {
        return $user->can('Create Goods Issue');
    }

    public function update(User $user, GoodsIssue $goodsIssue): bool
    {
        return $user->can('Update Goods Issue')
            && $goodsIssue->status !== GoodsIssueStatus::CANCELED;
    }

    public function delete(User $user, GoodsIssue $goodsIssue): bool
    {
        return $user->can('Delete Goods Issue')
            && $goodsIssue->status !== GoodsIssueStatus::CANCELED;
    }

    public function restore(User $user, GoodsIssue $goodsIssue): bool
    {
        return $user->can('Delete Goods Issue');
    }

    public function forceDelete(User $user, GoodsIssue $goodsIssue): bool
    {
        return $user->can('Delete Goods Issue');
    }
}
