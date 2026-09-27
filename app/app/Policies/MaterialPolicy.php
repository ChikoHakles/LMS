<?php

namespace App\Policies;

use App\Contracts\StudentMaterialAssignments;
use App\Models\Material;
use App\Models\User;

class MaterialPolicy
{
    public function __construct(private readonly StudentMaterialAssignments $assignments) {}

    public function viewAny(User $user): bool
    {
        return $user->hasRole(User::ROLE_ADMIN, User::ROLE_TUTOR, User::ROLE_STUDENT);
    }

    public function view(User $user, Material $material): bool
    {
        if ($user->isAdmin() || ($user->hasRole(User::ROLE_TUTOR) && $material->owner_id === $user->id)) {
            return true;
        }

        return $user->hasRole(User::ROLE_STUDENT)
            && $material->isPublished()
            && $this->assignments->isAssigned($user, $material);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(User::ROLE_TUTOR);
    }

    public function update(User $user, Material $material): bool
    {
        return $user->hasRole(User::ROLE_TUTOR) && $material->owner_id === $user->id;
    }

    public function publish(User $user, Material $material): bool
    {
        return $this->update($user, $material);
    }
}
