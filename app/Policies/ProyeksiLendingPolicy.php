<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ProyeksiLending;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProyeksiLendingPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_proyeksi::lending');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ProyeksiLending $proyeksiLending): bool
    {
        return $user->can('view_proyeksi::lending');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_proyeksi::lending');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ProyeksiLending $proyeksiLending): bool
    {
        if (!$user->hasRole('super_admin')) {
            if ($proyeksiLending->lending_tanggal->isPast()) {
                return false;
            }
        }
        return $user->can('update_proyeksi::lending');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ProyeksiLending $proyeksiLending): bool
    {
        return $user->can('delete_proyeksi::lending');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_proyeksi::lending');
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, ProyeksiLending $proyeksiLending): bool
    {
        return $user->can('force_delete_proyeksi::lending');
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_proyeksi::lending');
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, ProyeksiLending $proyeksiLending): bool
    {
        return $user->can('restore_proyeksi::lending');
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_proyeksi::lending');
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, ProyeksiLending $proyeksiLending): bool
    {
        return $user->can('replicate_proyeksi::lending');
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        return $user->can('reorder_proyeksi::lending');
    }
}
