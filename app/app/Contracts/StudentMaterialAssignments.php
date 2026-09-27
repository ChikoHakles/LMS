<?php

namespace App\Contracts;

use App\Models\Material;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Assignment boundary for student material access.
 *
 * The registered implementation resolves dated daily_plan_materials through
 * the student's current class membership.
 */
interface StudentMaterialAssignments
{
    /** @return Collection<int, int> Material primary keys assigned to this student. */
    public function materialIdsFor(User $student): Collection;

    public function isAssigned(User $student, Material $material): bool;
}
