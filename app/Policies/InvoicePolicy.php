<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('Read Invoice');
    }

    public function view(User $user, Invoice $invoice): bool
    {
        return $user->can('Read Invoice');
    }

    public function create(User $user): bool
    {
        return $user->can('Create Invoice');
    }

    public function update(User $user, Invoice $invoice): bool
    {
        return $user->can('Update Invoice');
    }

    public function delete(User $user, Invoice $invoice): bool
    {
        return $user->can('Delete Invoice');
    }

    public function restore(User $user, Invoice $invoice): bool
    {
        return $user->can('Delete Invoice');
    }

    public function forceDelete(User $user, Invoice $invoice): bool
    {
        return $user->can('Delete Invoice');
    }
}
