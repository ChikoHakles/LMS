<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\DailyPlanMaterial;
use App\Models\Material;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MaterialCompletionController extends Controller
{
    public function store(Request $request, DailyPlanMaterial $assignment): RedirectResponse
    {
        $student = $request->user();
        $assignment->load(['plan.learningClass.students', 'material']);
        abort_unless(in_array($assignment->type, [Material::TYPE_ARTICLE, Material::TYPE_VIDEO], true), 404);
        abort_unless($assignment->material->type === $assignment->type && $assignment->material->isPublished(), 404);
        abort_unless($assignment->plan->plan_date->toDateString() <= today()->toDateString(), 403);
        abort_unless($assignment->plan->learningClass->students->contains('id', $student->getKey()), 403);

        DB::transaction(function () use ($student, $assignment): void {
            $student->materialCompletions()->firstOrCreate([
                'daily_plan_material_id' => $assignment->getKey(),
            ], ['completed_at' => now()]);
        });

        return to_route('student.daily-rhythm', [
            'date' => $assignment->plan->plan_date->toDateString(),
            'class_id' => $assignment->plan->class_id,
        ])
            ->with('status', 'Materi ditandai selesai.');
    }
}
