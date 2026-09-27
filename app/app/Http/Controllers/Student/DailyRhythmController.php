<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\DailyPlanMaterial;
use App\Models\LearningClass;
use App\Models\Material;
use App\Models\PrayerLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DailyRhythmController extends Controller
{
    /** Human-readable labels are intentionally fixed with the five supported prayer IDs. */
    private const PRAYER_LABELS = [
        'subuh' => 'Subuh',
        'zuhur' => 'Zuhur',
        'asar' => 'Asar',
        'magrib' => 'Magrib',
        'isya' => 'Isya',
    ];

    public function show(Request $request): Response
    {
        $filters = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
            'class_id' => ['nullable', 'integer'],
        ]);
        $student = $request->user();
        $classes = $student->learningClasses()->orderBy('name')->get(['learning_classes.id', 'learning_classes.name']);
        $selectedClass = isset($filters['class_id'])
            ? $classes->firstWhere('id', (int) $filters['class_id'])
            : $classes->first();
        if (isset($filters['class_id']) && ! $selectedClass) {
            abort(404);
        }

        $date = $filters['date'] ?? today()->toDateString();
        $logs = PrayerLog::query()->where('user_id', $student->getKey())->whereDate('prayer_date', $date)
            ->pluck('completed', 'prayer');
        $plan = $selectedClass
            ? LearningClass::query()->whereKey($selectedClass->id)->firstOrFail()->dailyPlans()
                ->whereDate('plan_date', $date)
                ->with(['materials.material', 'materials.completions' => fn ($query) => $query->where('user_id', $student->getKey())])
                ->first()
            : null;
        $prayerCount = $logs->filter(fn ($completed) => (bool) $completed)->count();
        $materialCount = $plan?->materials->filter(fn (DailyPlanMaterial $slot) => $slot->completions->isNotEmpty())->count() ?? 0;

        return Inertia::render('student/DailyRhythm', [
            'date' => $date,
            'classes' => $classes,
            'selectedClassId' => $selectedClass?->id,
            'summary' => [
                'completed' => $prayerCount + $materialCount,
                'total' => 8,
                'prayersCompleted' => $prayerCount,
                'materialsCompleted' => $materialCount,
            ],
            'prayers' => collect(self::PRAYER_LABELS)->map(fn ($label, $id) => [
                'id' => $id,
                'label' => $label,
                'completed' => (bool) ($logs[$id] ?? false),
            ])->values(),
            'plan' => $plan ? [
                'id' => $plan->id,
                'className' => $selectedClass->name,
                'targetMinutes' => $plan->target_minutes,
                'activeMinutes' => 0,
                'materials' => $plan->materials->map(fn (DailyPlanMaterial $slot) => [
                    'id' => $slot->id,
                    'type' => $slot->type,
                    'materialId' => $slot->material_id,
                    'title' => $slot->material->title,
                    'summary' => $slot->material->summary,
                    'completed' => $slot->completions->isNotEmpty(),
                    'href' => $slot->type === Material::TYPE_QUIZ
                        ? route('student.quizzes.show', ['material' => $slot->material_id, 'assignment' => $slot->id], false)
                        : route('student.materials.show', ['material' => $slot->material_id, 'assignment' => $slot->id], false),
                ])->values(),
            ] : null,
        ]);
    }

    public function updatePrayer(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
            'prayer' => ['required', 'string', 'in:subuh,zuhur,asar,magrib,isya'],
            'completed' => ['required', 'boolean'],
            'class_id' => ['nullable', 'integer'],
        ]);
        if (isset($data['class_id'])) {
            abort_unless($request->user()->learningClasses()->whereKey($data['class_id'])->exists(), 404);
        }
        $request->user()->prayerLogs()->updateOrCreate([
            'prayer_date' => $data['date'],
            'prayer' => $data['prayer'],
        ], ['completed' => $data['completed']]);

        return to_route('student.daily-rhythm', array_filter([
            'date' => $data['date'],
            'class_id' => $data['class_id'] ?? null,
        ]))->with('status', 'Checklist sholat diperbarui.');
    }
}
