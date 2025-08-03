<?php

namespace App\Policies;

use App\Models\StatusDapem;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class StatusDapemPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_statusdapem');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, StatusDapem $statusDapem): bool
    {
        return $user->can('view_statusdapem');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_statusdapem');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, StatusDapem $statusDapem): bool
    {
        return $user->can('update_statusdapem');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, StatusDapem $statusDapem): bool
    {
        return $user->can('delete_statusdapem');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, StatusDapem $statusDapem): bool
    {
        return $user->can('restore_statusdapem');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, StatusDapem $statusDapem): bool
    {
        return $user->can('force_delete_statusdapem');
    }
}
