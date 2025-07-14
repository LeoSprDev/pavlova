<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['administrateur', 'responsable-service']);
    }

    public function view(User $user, User $model): bool
    {
        if ($user->hasRole('administrateur')) {
            return true;
        }

        if ($user->hasRole('responsable-service')) {
            return $user->service_id === $model->service_id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('administrateur');
    }

    public function update(User $user, User $model): bool
    {
        if ($user->hasRole('administrateur')) {
            return true;
        }

        if ($user->hasRole('responsable-service')) {
            return $user->service_id === $model->service_id;
        }

        return false;
    }

    public function delete(User $user, User $model): bool
    {
        return $user->hasRole('administrateur');
    }

    public function restore(User $user, User $model): bool
    {
        return $user->hasRole('administrateur');
    }

    public function forceDelete(User $user, User $model): bool
    {
        return $user->hasRole('administrateur');
    }
}