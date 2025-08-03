<?php

namespace App\Policies;

use App\Models\BranchOffice;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class BranchOfficePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_branchoffice');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, BranchOffice $branchOffice): bool
    {
        return $user->can('view_branchoffice', $branchOffice);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_branchoffice');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, BranchOffice $branchOffice): bool
    {
        return $user->can('update_branchoffice');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, BranchOffice $branchOffice): bool
    {
        return $user->can('delete_branchoffice');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, BranchOffice $branchOffice): bool
    {
        return $user->can('restore_branchoffice');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, BranchOffice $branchOffice): bool
    {
        return $user->can('force_delete_branchoffice');
    }
}
