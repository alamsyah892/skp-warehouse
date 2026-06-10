<?php

namespace App\Policies;

use App\Models\CashBankTransaction;
use App\Models\User;

class CashBankTransactionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('Read Cash Bank Transaction');
    }

    public function view(User $user, CashBankTransaction $cashBankTransaction): bool
    {
        return $user->can('Read Cash Bank Transaction');
    }

    public function create(User $user): bool
    {
        return $user->can('Create Cash Bank Transaction');
    }

    public function update(User $user, CashBankTransaction $cashBankTransaction): bool
    {
        return $user->can('Update Cash Bank Transaction');
    }

    public function delete(User $user, CashBankTransaction $cashBankTransaction): bool
    {
        return $user->can('Delete Cash Bank Transaction');
    }

    public function restore(User $user, CashBankTransaction $cashBankTransaction): bool
    {
        return $user->can('Delete Cash Bank Transaction');
    }

    public function forceDelete(User $user, CashBankTransaction $cashBankTransaction): bool
    {
        return $user->can('Delete Cash Bank Transaction');
    }
}
