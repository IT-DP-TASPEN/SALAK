<?php

namespace App\Policies;

use App\Models\SumberPembayaranLending;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class SumberPembayaranLendingPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_sumberpembayaranlending');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, SumberPembayaranLending $sumberPembayaranLending): bool
    {
        return $user->can('view_sumberpembayaranlending');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_sumberpembayaranlending');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, SumberPembayaranLending $sumberPembayaranLending): bool
    {
        return $user->can('update_sumberpembayaranlending');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, SumberPembayaranLending $sumberPembayaranLending): bool
    {
        return $user->can('delete_sumberpembayaranlending');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, SumberPembayaranLending $sumberPembayaranLending): bool
    {
        return $user->can('restore_sumberpembayaranlending');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, SumberPembayaranLending $sumberPembayaranLending): bool
    {
        return $user->can('force_delete_sumberpembayaranlending');
    }
}
