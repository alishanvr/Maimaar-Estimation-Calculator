<?php

namespace App\Policies;

use App\Models\Estimation;
use App\Models\User;

class EstimationPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Estimation $estimation): bool
    {
        return $user->isAdmin() || $user->id === $estimation->user_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Estimation $estimation): bool
    {
        return $user->isAdmin() || $user->id === $estimation->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Estimation $estimation): bool
    {
        return $user->isAdmin() || $user->id === $estimation->user_id;
    }

    /**
     * Determine whether the user can trigger calculation.
     */
    public function calculate(User $user, Estimation $estimation): bool
    {
        return $user->isAdmin() || $user->id === $estimation->user_id;
    }

    /**
     * Determine whether the user can clone the model.
     */
    public function clone(User $user, Estimation $estimation): bool
    {
        return $user->isAdmin() || $user->id === $estimation->user_id;
    }

    /**
     * Determine whether the user can create a revision.
     */
    public function createRevision(User $user, Estimation $estimation): bool
    {
        return $user->isAdmin() || $user->id === $estimation->user_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Estimation $estimation): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Estimation $estimation): bool
    {
        return $user->isAdmin();
    }
}
