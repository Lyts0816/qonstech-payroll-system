<?php

namespace App\Policies;

use App\Models\User;
use App\Models\pagibig;
use Illuminate\Auth\Access\Response;

class pagibigPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        //
        return $user->role === User::ROLE_ADMINUSER;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, pagibig $pagibig): bool
    {
        //
        return $user->role === User::ROLE_ADMINUSER;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->role === User::ROLE_ADMINUSER;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, pagibig $pagibig): bool
    {
        return $user->role === User::ROLE_ADMINUSER;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, pagibig $pagibig): bool
    {
        return $user->role === User::ROLE_ADMINUSER;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, pagibig $pagibig): bool
    {
        return $user->role === User::ROLE_ADMINUSER;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, pagibig $pagibig): bool
    {
        return $user->role === User::ROLE_ADMINUSER;
    }
}
