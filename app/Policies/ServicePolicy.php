<?php

namespace App\Policies;

use App\Models\Service;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ServicePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['administrateur', 'responsable-budget', 'responsable-direction']);
    }

    public function view(User $user, Service $service): bool
    {
        return $user->hasAnyRole(['administrateur', 'responsable-budget', 'responsable-direction']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['administrateur', 'responsable-budget']);
    }

    public function update(User $user, Service $service): bool
    {
        return $user->hasAnyRole(['administrateur', 'responsable-budget']);
    }

    public function delete(User $user, Service $service): bool
    {
        return $user->hasRole('administrateur');
    }

    public function restore(User $user, Service $service): bool
    {
        return $user->hasRole('administrateur');
    }

    public function forceDelete(User $user, Service $service): bool
    {
        return $user->hasRole('administrateur');
    }
}