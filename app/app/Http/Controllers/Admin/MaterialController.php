<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Material;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class MaterialController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::forUser($request->user())->authorize('viewAny', Material::class);

        $type = $request->query('type');
        $search = trim((string) $request->query('q', ''));
        $materials = Material::query()
            ->when(in_array($type, Material::TYPES, true), fn ($query) => $query->where('type', $type))
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('title', 'like', '%'.$search.'%')
                    ->orWhere('summary', 'like', '%'.$search.'%');
            }))
            ->with('owner:id,name')
            ->latest('updated_at')
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('tutor/materials/Index', [
            'materials' => $materials,
            'filters' => ['q' => $search, 'type' => $type, 'status' => null],
            'types' => Material::TYPES,
            'readOnly' => true,
        ]);
    }
}
