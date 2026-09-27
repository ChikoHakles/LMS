<?php

namespace App\Contracts;

use App\Models\Material;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Assignment boundary for student material access.
 *
 * RUANG-40 should back these methods with daily_plan_materials. Until then,
 * the registered implementation returns no assignments and denies reads.
 */
interface StudentMaterialAssignments
{
    /** @return Collection<int, int> Material primary keys assigned to this student. */
    public function materialIdsFor(User $student): Collection;

    public function isAssigned(User $student, Material $material): bool;
}
