<?php

namespace App\Services;

use App\Contracts\StudentMaterialAssignments;
use App\Models\DailyPlanMaterial;
use App\Models\Material;
use App\Models\User;
use Illuminate\Support\Collection;

/** Resolves only dated assignments that still belong to one of the student's classes. */
class DailyPlanStudentMaterialAssignments implements StudentMaterialAssignments
{
    public function materialIdsFor(User $student): Collection
    {
        return DailyPlanMaterial::query()
            ->whereHas('plan.learningClass.students', fn ($query) => $query->whereKey($student->getKey()))
            ->whereHas('material', fn ($query) => $query->published())
            ->pluck('material_id')
            ->unique()
            ->values();
    }

    public function isAssigned(User $student, Material $material): bool
    {
        return DailyPlanMaterial::query()
            ->where('material_id', $material->getKey())
            ->where('type', $material->type)
            ->whereHas('plan.learningClass.students', fn ($query) => $query->whereKey($student->getKey()))
            ->exists();
    }
}
