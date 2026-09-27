<?php

namespace App\Http\Controllers\Student;

use App\Contracts\StudentMaterialAssignments;
use App\Http\Controllers\Controller;
use App\Models\Material;
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

    public function show(Material $material): Response
    {
        Gate::authorize('view', $material);
        abort_unless($material->type === Material::TYPE_ARTICLE, 404);

        return Inertia::render('materials/Show', [
            'material' => $material->only(['id', 'type', 'title', 'summary', 'published_at', 'blocks']),
            'canEdit' => false,
        ]);
    }
}
