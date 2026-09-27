<?php

namespace App\Http\Controllers\Student;

use App\Contracts\StudentMaterialAssignments;
use App\Http\Controllers\Controller;
use App\Models\DailyPlanMaterial;
use App\Models\Material;
use App\Models\MaterialCompletion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class MaterialController extends Controller
{
    public function index(Request $request, StudentMaterialAssignments $assignments): Response
    {
        Gate::forUser($request->user())->authorize('viewAny', Material::class);
        $ids = $assignments->materialIdsFor($request->user());

        $materials = Material::query()
            ->published()
            ->whereIn('id', $ids)
            ->latest('published_at')
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('student/Materials/Index', ['materials' => $materials]);
    }

    public function show(Request $request, Material $material): Response
    {
        Gate::authorize('view', $material);
        $assignment = null;
        if ($request->query('assignment') !== null) {
            $assignmentId = filter_var($request->query('assignment'), FILTER_VALIDATE_INT);
            abort_unless($assignmentId !== false, 404);
            $assignment = DailyPlanMaterial::query()
                ->whereKey($assignmentId)
                ->where('material_id', $material->getKey())
                ->where('type', $material->type)
                ->whereHas('plan.learningClass.students', fn ($query) => $query->whereKey($request->user()->getKey()))
                ->firstOrFail();
        }

        if ($material->type === Material::TYPE_VIDEO) {
            $embedUrl = $material->youtubeEmbedUrl();
            abort_unless($embedUrl !== null, 404);

            return Inertia::render('student/Video', [
                'material' => $material->only(['id', 'type', 'title', 'summary', 'published_at']) + [
                    'embedUrl' => $embedUrl,
                    'assignmentId' => $assignment?->getKey(),
                    'completed' => $assignment ? MaterialCompletion::query()->where('user_id', $request->user()->getKey())
                        ->where('daily_plan_material_id', $assignment->getKey())->exists() : false,
                ],
            ]);
        }

        abort_unless($material->type === Material::TYPE_ARTICLE, 404);

        return Inertia::render('materials/Show', [
            'material' => $material->only(['id', 'type', 'title', 'summary', 'published_at', 'blocks']),
            'canEdit' => false,
            'assignmentId' => $assignment?->getKey(),
            'completed' => $assignment ? MaterialCompletion::query()->where('user_id', $request->user()->getKey())
                ->where('daily_plan_material_id', $assignment->getKey())->exists() : false,
        ]);
    }
}
