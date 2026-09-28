<?php

namespace Tests\Feature;

use App\Models\DailyPlan;
use App\Models\DailyPlanMaterial;
use App\Models\LearningClass;
use App\Models\Material;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\StudySession;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_administrators_keep_the_original_dashboard_page(): void
    {
        $admin = $this->userWithRole(User::ROLE_ADMIN);

        $this->actingAs($admin)->get('/dashboard')->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->missing('summary')
            ->missing('classes'));
    }

    public function test_student_dashboard_contains_only_their_own_today_assignments_and_activity(): void
    {
        $this->travelTo(Carbon::parse('2026-09-28 10:00:00', 'Asia/Jakarta'));
        $tutor = $this->userWithRole(User::ROLE_TUTOR);
        $student = $this->userWithRole(User::ROLE_STUDENT);
        $otherStudent = $this->userWithRole(User::ROLE_STUDENT);
        [, $ownPlan, $ownSlot] = $this->planFor($tutor, $student, 'Kelas milik saya', '2026-09-28', 'Materi saya');
        [, , $otherSlot] = $this->planFor($tutor, $otherStudent, 'Kelas lain', '2026-09-28', 'Materi murid lain');
        $student->prayerLogs()->create(['prayer_date' => '2026-09-28', 'prayer' => 'subuh', 'completed' => true]);
        $student->materialCompletions()->create([
            'daily_plan_material_id' => $ownSlot->id,
            'completed_at' => now(),
        ]);
        $this->addStudySeconds($student, $ownPlan, $ownSlot, 125);

        $this->actingAs($student)->get('/dashboard')->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('date', '2026-09-28')
            ->where('summary.prayersCompleted', 1)
            ->where('summary.prayersTotal', 5)
            ->where('summary.materialsCompleted', 1)
            ->where('summary.materialsTotal', 1)
            ->where('summary.activeMinutes', 2)
            ->where('agenda.0.className', 'Kelas milik saya '.$student->id)
            ->where('agenda.0.materials.0.title', 'Materi saya')
            ->where('newMaterials', [])
            ->where('deadlines', [])
            ->where('priorities', []));

        $this->assertDatabaseHas('daily_plan_materials', ['id' => $otherSlot->id]);
    }

    public function test_student_dashboard_uses_the_asia_jakarta_calendar_date_and_has_empty_state_data(): void
    {
        $this->travelTo(Carbon::parse('2026-09-27 17:30:00', 'UTC'));
        $student = $this->userWithRole(User::ROLE_STUDENT);

        $this->actingAs($student)->get('/dashboard')->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('date', '2026-09-28')
            ->where('summary.prayersCompleted', 0)
            ->where('summary.materialsCompleted', 0)
            ->where('summary.materialsTotal', 0)
            ->where('summary.activeMinutes', 0)
            ->where('agenda', [])
            ->where('newMaterials', [])
            ->where('deadlines', []));
    }

    public function test_tutor_dashboard_and_progress_are_limited_to_the_tutors_classes(): void
    {
        $this->travelTo(Carbon::parse('2026-09-28 10:00:00', 'Asia/Jakarta'));
        $tutor = $this->userWithRole(User::ROLE_TUTOR);
        $otherTutor = $this->userWithRole(User::ROLE_TUTOR);
        $student = $this->userWithRole(User::ROLE_STUDENT);
        $otherStudent = $this->userWithRole(User::ROLE_STUDENT);
        [$ownClass, $ownPlan, $ownSlot, $quiz] = $this->quizPlanFor($tutor, $student, 'Kelas tutor', '2026-09-28');
        [$otherClass] = $this->planFor($otherTutor, $otherStudent, 'Kelas rahasia', '2026-09-28', 'Materi rahasia');
        $question = $quiz->quizQuestions()->create([
            'type' => QuizQuestion::TYPE_ESSAY,
            'prompt' => 'Jelaskan jawabanmu',
            'points' => 5,
            'position' => 1,
        ]);
        $attempt = $quiz->attempts()->create([
            'student_id' => $student->id,
            'daily_plan_material_id' => $ownSlot->id,
            'status' => QuizAttempt::STATUS_AWAITING_REVIEW,
            'submitted_at' => now(),
        ]);
        $attempt->answers()->create(['question_id' => $question->id, 'response' => 'Jawaban murid']);

        $this->actingAs($tutor)->get('/dashboard')->assertInertia(fn (Assert $page) => $page
            ->component('tutor/Dashboard')
            ->where('summary.classCount', 1)
            ->where('summary.studentCount', 1)
            ->where('summary.submissionCount', 1)
            ->where('summary.essayQueueCount', 1)
            ->where('classes.0.name', 'Kelas tutor')
            ->where('progressPreview.0.name', $student->name)
            ->where('progressPreview.0.materialsTotal', 1)
            ->missing('otherStudent'));

        $this->get(route('tutor.students.progress', absolute: false))->assertInertia(fn (Assert $page) => $page
            ->component('tutor/StudentProgress')
            ->where('classes.0.id', $ownClass->id)
            ->where('students.0.id', $student->id)
            ->where('students.0.materialsTotal', 1)
            ->where('students.0.prayersCompleted', 0)
            ->where('students.0.activeMinutes', 0)
            ->missing('otherClass')
            ->missing('otherStudent'));

        $this->assertDatabaseHas('daily_plans', ['id' => $ownPlan->id, 'class_id' => $ownClass->id]);
        $this->assertDatabaseHas('learning_classes', ['id' => $otherClass->id, 'tutor_id' => $otherTutor->id]);
    }

    public function test_tutor_progress_empty_state_and_other_roles_cannot_open_it(): void
    {
        $tutor = $this->userWithRole(User::ROLE_TUTOR);
        $this->actingAs($tutor)->get(route('tutor.students.progress', absolute: false))
            ->assertInertia(fn (Assert $page) => $page
                ->component('tutor/StudentProgress')
                ->where('classes', [])
                ->where('students', []));

        $student = $this->userWithRole(User::ROLE_STUDENT);
        $this->actingAs($student)->get(route('tutor.students.progress', absolute: false))->assertForbidden();
    }

    /** @return array{LearningClass, DailyPlan, DailyPlanMaterial, Material} */
    private function quizPlanFor(User $tutor, User $student, string $className, string $date): array
    {
        $class = $tutor->tutorClasses()->create(['name' => $className]);
        $class->students()->attach($student->id);
        $plan = $class->dailyPlans()->create(['plan_date' => $date, 'target_minutes' => 45, 'tutor_id' => $tutor->id]);
        $quiz = $tutor->materials()->create([
            'type' => Material::TYPE_QUIZ,
            'title' => 'Kuis kelas',
            'summary' => 'Kuis untuk kelas',
            'status' => Material::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);
        $slot = $plan->materials()->create([
            'material_id' => $quiz->id,
            'tutor_id' => $tutor->id,
            'type' => Material::TYPE_QUIZ,
            'position' => 1,
        ]);

        return [$class, $plan, $slot, $quiz];
    }

    /** @return array{LearningClass, DailyPlan, DailyPlanMaterial} */
    private function planFor(User $tutor, User $student, string $className, string $date, string $title): array
    {
        $class = $tutor->tutorClasses()->create(['name' => $className.' '.$student->id]);
        $class->students()->attach($student->id);
        $plan = $class->dailyPlans()->create(['plan_date' => $date, 'target_minutes' => 45, 'tutor_id' => $tutor->id]);
        $material = $tutor->materials()->create([
            'type' => Material::TYPE_ARTICLE,
            'title' => $title,
            'summary' => 'Ringkasan materi',
            'status' => Material::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);
        $slot = $plan->materials()->create([
            'material_id' => $material->id,
            'tutor_id' => $tutor->id,
            'type' => Material::TYPE_ARTICLE,
            'position' => 1,
        ]);

        return [$class, $plan, $slot];
    }

    private function addStudySeconds(User $student, DailyPlan $plan, DailyPlanMaterial $slot, int $seconds): void
    {
        $session = StudySession::query()->create([
            'public_id' => (string) Str::uuid(),
            'user_id' => $student->id,
            'active_user_id' => null,
            'daily_plan_id' => $plan->id,
            'daily_plan_material_id' => $slot->id,
            'material_id' => $slot->material_id,
            'started_at' => now()->subSeconds($seconds),
            'last_seen_at' => now(),
            'ended_at' => now(),
            'seconds' => $seconds,
            'last_sequence' => 1,
        ]);
        $session->intervals()->create([
            'study_date' => today()->toDateString(),
            'sequence' => 1,
            'seconds' => $seconds,
        ]);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->forceFill(['role' => $role])->save();

        return $user;
    }
}
