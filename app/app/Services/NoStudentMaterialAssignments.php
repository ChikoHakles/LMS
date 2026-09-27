<?php

namespace App\Services;

use App\Contracts\StudentMaterialAssignments;
use App\Models\Material;
use App\Models\User;
use Illuminate\Support\Collection;

/** Default-deny adapter until the daily-plan assignment tables are delivered. */
class NoStudentMaterialAssignments implements StudentMaterialAssignments
{
    public function materialIdsFor(User $student): Collection
    {
        return collect();
    }

    public function isAssigned(User $student, Material $material): bool
    {
        return false;
    }
}
