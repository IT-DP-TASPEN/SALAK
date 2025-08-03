<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ProdukLending;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProdukLendingPolicy
{
    use HandlesAuthorization;

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
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_produklending');
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, ProdukLending $produkLending): bool
    {
        return $user->can('force_delete_produklending');
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_produklending');
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, ProdukLending $produkLending): bool
    {
        return $user->can('restore_produklending');
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_produklending');
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, ProdukLending $produkLending): bool
    {
        return $user->can('replicate_produklending');
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        return $user->can('reorder_produklending');
    }
}
