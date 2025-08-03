<?php

namespace App\Policies;

use App\Models\StatusKerja;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class StatusKerjaPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_statuskerja');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, StatusKerja $statusKerja): bool
    {
        return $user->can('view_statuskerja');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_statuskerja');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, StatusKerja $statusKerja): bool
    {
        return $user->can('update_statuskerja');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, StatusKerja $statusKerja): bool
    {
        return $user->can('delete_statuskerja');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, StatusKerja $statusKerja): bool
    {
        return $user->can('restore_statuskerja');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, StatusKerja $statusKerja): bool
    {
        return $user->can('force_delete_statuskerja');
    }
}
