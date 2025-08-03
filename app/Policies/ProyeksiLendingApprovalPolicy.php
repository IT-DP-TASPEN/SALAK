<?php

namespace App\Policies;

use App\Models\ProyeksiLendingApproval;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ProyeksiLendingApprovalPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_proyeksilendingapproval');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ProyeksiLendingApproval $proyeksiLendingApproval): bool
    {
        return $user->can('view_proyeksilendingapproval');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_proyeksilendingapproval');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ProyeksiLendingApproval $proyeksiLendingApproval): bool
    {
        return $user->can('update_proyeksilendingapproval');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ProyeksiLendingApproval $proyeksiLendingApproval): bool
    {
        return $user->can('delete_proyeksilendingapproval');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ProyeksiLendingApproval $proyeksiLendingApproval): bool
    {
        return $user->can('restore_proyeksilendingapproval');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ProyeksiLendingApproval $proyeksiLendingApproval): bool
    {
        return $user->can('force_delete_proyeksilendingapproval');
    }
}
