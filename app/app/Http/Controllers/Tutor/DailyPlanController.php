<?php

namespace App\Http\Controllers\Tutor;

use App\Http\Controllers\Controller;
use App\Models\DailyPlan;
use App\Models\LearningClass;
use App\Models\Material;
use App\Models\MaterialCompletion;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class DailyPlanController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'class_id' => ['nullable', 'integer'],
            'date' => ['nullable', 'date_format:Y-m-d'],
        ]);
        $tutor = $request->user();
        $classes = LearningClass::query()->where('tutor_id', $tutor->getKey())->withCount('students')->orderBy('name')->get();
        $selectedClass = isset($filters['class_id'])
            ? $classes->firstWhere('id', (int) $filters['class_id'])
            : $classes->first();
        if (isset($filters['class_id']) && ! $selectedClass) {
            abort(404);
        }

        $date = $filters['date'] ?? today()->toDateString();
        $plan = $selectedClass?->dailyPlans()
            ->whereDate('plan_date', $date)
            ->with(['materials.material', 'materials.completions'])
            ->first();
        $students = User::query()
            ->where('role', User::ROLE_STUDENT)
            ->where('status', User::STATUS_ACTIVE)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return Inertia::render('tutor/DailyPlan', [
            'classes' => $classes,
            'students' => $students,
            'selectedClass' => $selectedClass ? [
                'id' => $selectedClass->id,
                'name' => $selectedClass->name,
                'studentIds' => $selectedClass->students()->orderBy('users.name')->pluck('users.id'),
            ] : null,
            'plan' => $plan ? [
                'id' => $plan->id,
                'date' => $plan->plan_date->toDateString(),
                'target_minutes' => $plan->target_minutes,
                'locked' => $plan->materials->contains(fn ($slot) => $slot->completions->isNotEmpty()),
                'materials' => $plan->materials->map(fn ($slot) => [
                    'id' => $slot->id,
                    'type' => $slot->type,
                    'material_id' => $slot->material_id,
                    'title' => $slot->material->title,
                    'position' => $slot->position,
                ])->values(),
            ] : null,
            'date' => $date,
            'materials' => Material::query()->ownedBy($tutor)->published()->orderBy('type')->orderBy('title')
                ->get(['id', 'type', 'title']),
            'prayers' => ['subuh', 'zuhur', 'asar', 'magrib', 'isya'],
        ]);
    }

    public function storeClass(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => [
                'required', 'string', 'max:120',
                Rule::unique('learning_classes', 'name')->where(fn ($query) => $query->where('tutor_id', $request->user()->getKey())),
            ],
        ]);
        $class = new LearningClass(['name' => trim($data['name'])]);
        $class->tutor()->associate($request->user());
        $class->save();

        return to_route('tutor.daily-plans.index', ['class_id' => $class->id])->with('status', 'Kelas berhasil dibuat. Tambahkan murid untuk membagikan tasklist.');
    }

    public function updateStudents(Request $request, LearningClass $learningClass): RedirectResponse
    {
        $this->ownedClass($request, $learningClass);
        $data = $request->validate([
            'student_ids' => ['present', 'array', 'max:500'],
            'student_ids.*' => ['integer', 'distinct', 'exists:users,id'],
        ]);
        $ids = collect($data['student_ids']);
        $validCount = User::query()->whereIn('id', $ids)->where('role', User::ROLE_STUDENT)
            ->where('status', User::STATUS_ACTIVE)->count();
        if ($validCount !== $ids->count()) {
            throw ValidationException::withMessages(['student_ids' => 'Pilih akun murid aktif yang valid.']);
        }
        $removedIds = $learningClass->students()->pluck('users.id')->diff($ids);
        if ($removedIds->isNotEmpty() && MaterialCompletion::query()->whereIn('user_id', $removedIds)
            ->whereHas('planMaterial.plan', fn ($query) => $query->where('class_id', $learningClass->getKey()))->exists()) {
            throw ValidationException::withMessages(['student_ids' => 'Murid dengan penyelesaian tersimpan tidak dapat dikeluarkan dari kelas agar riwayatnya tetap tersedia.']);
        }
        $learningClass->students()->sync($ids->all());

        return to_route('tutor.daily-plans.index', ['class_id' => $learningClass->id])->with('status', 'Daftar murid kelas berhasil disimpan.');
    }

    public function store(Request $request): RedirectResponse
    {
        $classData = $request->validate(['class_id' => ['required', 'integer', 'exists:learning_classes,id']]);
        $tutor = $request->user();
        $class = LearningClass::query()->whereKey($classData['class_id'])->where('tutor_id', $tutor->getKey())->firstOrFail();
        $data = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
            'target_minutes' => ['required', 'integer', 'min:1', 'max:600'],
            'materials' => ['required', 'array', 'size:3'],
            'materials.*.type' => ['required', 'string', 'in:article,video,quiz', 'distinct'],
            'materials.*.material_id' => ['required', 'integer', 'distinct', 'exists:materials,id'],
        ]);
        foreach ($data['materials'] as $slot) {
            $material = Material::query()->whereKey($slot['material_id'])->where('owner_id', $tutor->getKey())->first();
            if (! $material || $material->type !== $slot['type'] || ! $material->isPublished()) {
                throw ValidationException::withMessages([
                    'materials' => 'Setiap pilihan harus berupa materi terbit milik Anda dengan jenis yang sesuai.',
                ]);
            }
        }

        DB::transaction(function () use ($class, $tutor, $data): void {
            // Serialize writers for this class; the unique class/date key is the final race guard.
            $lockedClass = LearningClass::query()->whereKey($class->getKey())->lockForUpdate()->firstOrFail();
            $plan = $lockedClass->dailyPlans()->whereDate('plan_date', $data['date'])->lockForUpdate()->first();
            if ($plan && $plan->materials()->whereHas('completions')->exists()) {
                throw ValidationException::withMessages([
                    'plan' => 'Tasklist ini sudah memiliki penyelesaian murid dan tidak dapat diubah agar riwayat tetap utuh.',
                ]);
            }

            $plan ??= new DailyPlan;
            $plan->forceFill([
                'plan_date' => $data['date'],
                'target_minutes' => $data['target_minutes'],
                'tutor_id' => $tutor->getKey(),
            ]);
            $plan->learningClass()->associate($lockedClass);
            $plan->save();
            $plan->materials()->delete();

            $order = ['article' => 1, 'video' => 2, 'quiz' => 3];
            foreach ($data['materials'] as $slot) {
                $plan->materials()->create([
                    'material_id' => $slot['material_id'],
                    'tutor_id' => $tutor->getKey(),
                    'type' => $slot['type'],
                    'position' => $order[$slot['type']],
                ]);
            }
        });

        return to_route('tutor.daily-plans.index', ['class_id' => $class->id, 'date' => $data['date']])
            ->with('status', 'Tasklist harian berhasil disimpan.');
    }

    private function ownedClass(Request $request, LearningClass $learningClass): LearningClass
    {
        abort_unless($learningClass->tutor_id === $request->user()->getKey(), 404);

        return $learningClass;
    }
}
