<?php

namespace App\Http\Controllers;

use App\Models\DailyPlan;
use App\Models\DailyPlanMaterial;
use App\Models\Material;
use App\Models\MaterialCompletion;
use App\Models\PrayerLog;
use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        if ($user->hasRole(User::ROLE_STUDENT)) {
            return Inertia::render('Dashboard', $this->studentSummary($user));
        }

        if ($user->hasRole(User::ROLE_TUTOR)) {
            return Inertia::render('tutor/Dashboard', $this->tutorSummary($user));
        }

        // Keep the existing account-management landing page for administrators.
        return Inertia::render('AdminDashboard');
    }

    public function studentProgress(Request $request): Response
    {
        abort_unless($request->user()->hasRole(User::ROLE_TUTOR), 403);

        return Inertia::render('tutor/StudentProgress', $this->progressSummary($request->user()));
    }

    /** @return array<string, mixed> */
    private function studentSummary(User $student): array
    {
        $date = Carbon::today(config('app.timezone'))->toDateString();
        $plans = DailyPlan::query()
            ->whereDate('plan_date', $date)
            ->whereHas('learningClass.students', fn ($query) => $query->whereKey($student->getKey()))
            ->with([
                'learningClass:id,name',
                'materials' => fn ($query) => $query->whereHas('material', fn ($material) => $material->published())
                    ->with(['material' => fn ($material) => $material->published()])
                    ->with(['completions' => fn ($query) => $query->where('user_id', $student->getKey())]),
            ])
            ->orderBy('class_id')
            ->get();

        $assignments = $plans->flatMap(fn (DailyPlan $plan) => $plan->materials->map(fn (DailyPlanMaterial $slot) => [
            'id' => $slot->id,
            'planId' => $plan->id,
            'type' => $slot->type,
            'title' => $slot->material->title,
            'summary' => $slot->material->summary,
            'className' => $plan->learningClass->name,
            'completed' => $slot->completions->isNotEmpty(),
            'href' => $slot->type === Material::TYPE_QUIZ
                ? route('student.quizzes.show', ['material' => $slot->material_id, 'assignment' => $slot->id], false)
                : route('student.materials.show', ['material' => $slot->material_id, 'assignment' => $slot->id], false),
        ]))->values();

        $prayersCompleted = PrayerLog::query()
            ->where('user_id', $student->getKey())
            ->whereDate('prayer_date', $date)
            ->where('completed', true)
            ->count();
        $activeSeconds = DB::table('study_session_intervals')
            ->join('study_sessions', 'study_sessions.id', '=', 'study_session_intervals.study_session_id')
            ->where('study_sessions.user_id', $student->getKey())
            ->whereDate('study_session_intervals.study_date', $date)
            ->sum('study_session_intervals.seconds');
        $materialsCompleted = $assignments->where('completed', true)->count();

        return [
            'date' => $date,
            'summary' => [
                'prayersCompleted' => $prayersCompleted,
                'prayersTotal' => count(PrayerLog::PRAYERS),
                'materialsCompleted' => $materialsCompleted,
                'materialsTotal' => $assignments->count(),
                'activeMinutes' => intdiv((int) $activeSeconds, 60),
                'targetMinutes' => (int) $plans->sum('target_minutes'),
            ],
            'agenda' => $plans->map(fn (DailyPlan $plan) => [
                'id' => $plan->id,
                'className' => $plan->learningClass->name,
                'targetMinutes' => $plan->target_minutes,
                'materials' => $assignments->where('planId', $plan->id)->values(),
            ])->values(),
            'newMaterials' => $assignments->where('completed', false)->values(),
            'deadlines' => [],
            'priorities' => $assignments->where('completed', false)->take(3)->values(),
            'prayers' => PrayerLog::PRAYERS,
        ];
    }

    /** @return array<string, mixed> */
    private function tutorSummary(User $tutor): array
    {
        $date = Carbon::today(config('app.timezone'))->toDateString();
        $classes = $tutor->tutorClasses()
            ->withCount('students')
            ->with(['dailyPlans' => fn ($query) => $query->whereDate('plan_date', $date)->withCount('materials')])
            ->orderBy('name')
            ->get(['id', 'name']);
        $classIds = $classes->modelKeys();
        $progress = $this->studentProgressRows($tutor, $classIds, $date);

        $submissions = QuizAttempt::query()
            ->whereHas('dailyPlanMaterial.plan.learningClass', fn ($query) => $query->where('tutor_id', $tutor->getKey()))
            ->count();
        $essayQueue = $this->essayQueueQuery($tutor)->count();

        return [
            'date' => $date,
            'summary' => [
                'classCount' => $classes->count(),
                'studentCount' => $progress->count(),
                'todayPlanCount' => $classes->sum(fn ($class) => $class->dailyPlans->count()),
                'materialCount' => Material::query()->ownedBy($tutor)->published()->count(),
                'submissionCount' => $submissions,
                'essayQueueCount' => $essayQueue,
            ],
            'classes' => $classes->map(fn ($class) => [
                'id' => $class->id,
                'name' => $class->name,
                'studentCount' => $class->students_count,
                'todayPlanCount' => $class->dailyPlans->count(),
            ])->values(),
            'progressPreview' => $progress->take(5)->values(),
        ];
    }

    /** @return array<string, mixed> */
    private function progressSummary(User $tutor): array
    {
        $date = Carbon::today(config('app.timezone'))->toDateString();
        $classes = $tutor->tutorClasses()->with('students:id,name')->orderBy('name')->get(['id', 'name']);
        $classIds = $classes->modelKeys();

        return [
            'date' => $date,
            'classes' => $classes->map(fn ($class) => [
                'id' => $class->id,
                'name' => $class->name,
            ])->values(),
            'students' => $this->studentProgressRows($tutor, $classIds, $date),
        ];
    }

    /** @param list<int> $classIds
     * @return Collection<int, array<string, mixed>>
     */
    private function studentProgressRows(User $tutor, array $classIds, string $date)
    {
        $students = User::query()
            ->where('role', User::ROLE_STUDENT)
            ->whereHas('learningClasses', fn ($query) => $query->whereIn('learning_classes.id', $classIds)
                ->where('learning_classes.tutor_id', $tutor->getKey()))
            ->with(['learningClasses' => fn ($query) => $query->whereIn('learning_classes.id', $classIds)
                ->where('learning_classes.tutor_id', $tutor->getKey())->orderBy('name')])
            ->orderBy('name')
            ->get(['id', 'name']);
        $studentIds = $students->modelKeys();

        if ($studentIds === [] || $classIds === []) {
            return $students->map(fn (User $student) => $this->studentProgressRow($student, collect(), collect(), collect()))->values();
        }

        $prayerCounts = PrayerLog::query()->select('user_id', DB::raw('COUNT(*) as completed_count'))
            ->whereIn('user_id', $studentIds)->whereDate('prayer_date', $date)->where('completed', true)
            ->groupBy('user_id')->pluck('completed_count', 'user_id');
        $materialCounts = MaterialCompletion::query()
            ->join('daily_plan_materials', 'daily_plan_materials.id', '=', 'material_completions.daily_plan_material_id')
            ->join('daily_plans', 'daily_plans.id', '=', 'daily_plan_materials.daily_plan_id')
            ->whereIn('material_completions.user_id', $studentIds)
            ->whereIn('daily_plans.class_id', $classIds)->where('daily_plans.tutor_id', $tutor->getKey())
            ->whereDate('daily_plans.plan_date', $date)
            ->select('material_completions.user_id', DB::raw('COUNT(*) as completed_count'))
            ->groupBy('material_completions.user_id')->pluck('completed_count', 'user_id');
        $materialTotals = DB::table('daily_plan_materials')
            ->join('daily_plans', 'daily_plans.id', '=', 'daily_plan_materials.daily_plan_id')
            ->join('class_students', 'class_students.class_id', '=', 'daily_plans.class_id')
            ->whereIn('class_students.student_id', $studentIds)->whereIn('daily_plans.class_id', $classIds)
            ->where('daily_plans.tutor_id', $tutor->getKey())->whereDate('daily_plans.plan_date', $date)
            ->select('class_students.student_id', DB::raw('COUNT(daily_plan_materials.id) as total_count'))
            ->groupBy('class_students.student_id')->pluck('total_count', 'student_id');
        $activeSeconds = DB::table('study_session_intervals')
            ->join('study_sessions', 'study_sessions.id', '=', 'study_session_intervals.study_session_id')
            ->join('daily_plans', 'daily_plans.id', '=', 'study_sessions.daily_plan_id')
            ->whereIn('study_sessions.user_id', $studentIds)->whereIn('daily_plans.class_id', $classIds)
            ->where('daily_plans.tutor_id', $tutor->getKey())->whereDate('study_session_intervals.study_date', $date)
            ->select('study_sessions.user_id', DB::raw('SUM(study_session_intervals.seconds) as total_seconds'))
            ->groupBy('study_sessions.user_id')->pluck('total_seconds', 'user_id');

        return $students->map(fn (User $student) => $this->studentProgressRow(
            $student,
            $prayerCounts,
            $materialCounts,
            $materialTotals,
            $activeSeconds,
        ))->values();
    }

    /** @param Collection<int|string, int|string> $prayers
     * @param  Collection<int|string, int|string>  $materials
     * @param  Collection<int|string, int|string>  $materialTotals
     * @param  Collection<int|string, int|string>  $activeSeconds
     * @return array<string, mixed>
     */
    private function studentProgressRow(User $student, $prayers, $materials, $materialTotals, $activeSeconds = null): array
    {
        return [
            'id' => $student->id,
            'name' => $student->name,
            'classes' => $student->learningClasses->pluck('name')->values(),
            'prayersCompleted' => (int) ($prayers[$student->id] ?? 0),
            'prayersTotal' => count(PrayerLog::PRAYERS),
            'materialsCompleted' => (int) ($materials[$student->id] ?? 0),
            'materialsTotal' => (int) ($materialTotals[$student->id] ?? 0),
            'activeMinutes' => intdiv((int) ($activeSeconds[$student->id] ?? 0), 60),
        ];
    }

    private function essayQueueQuery(User $tutor): Builder
    {
        return QuizAnswer::query()
            ->whereNull('graded_at')
            ->whereHas('question', fn ($query) => $query->where('type', QuizQuestion::TYPE_ESSAY))
            ->whereHas('attempt', fn ($query) => $query
                ->where('status', QuizAttempt::STATUS_AWAITING_REVIEW)
                ->whereHas('material', fn ($query) => $query->where('owner_id', $tutor->getKey())));
    }
}
