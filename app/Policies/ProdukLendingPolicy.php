<?php

namespace App\Policies;

use App\Models\ProdukLending;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ProdukLendingPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_produklending');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ProdukLending $produkLending): bool
    {
        return $user->can('view_produklending');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_produklending');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ProdukLending $produkLending): bool
    {
        return $user->can('update_produklending');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ProdukLending $produkLending): bool
    {
        return $user->can('delete_produklending');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ProdukLending $produkLending): bool
    {
        return $user->can('restore_produklending');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ProdukLending $produkLending): bool
    {
        return $user->can('force_delete_produklending');
    }
}
