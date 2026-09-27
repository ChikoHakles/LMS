<?php

namespace Tests\Feature;

use App\Models\DailyPlan;
use App\Models\DailyPlanMaterial;
use App\Models\LearningClass;
use App\Models\Material;
use App\Models\MaterialCompletion;
use App\Models\PrayerLog;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentRhythmTest extends TestCase
{
    use RefreshDatabase;

    public function test_prayer_checklist_is_fixed_idempotent_and_separated_by_date(): void
    {
        $student = $this->userWithRole(User::ROLE_STUDENT);
        $this->actingAs($student);
        $date = '2026-09-27';

        foreach (PrayerLog::PRAYERS as $prayer) {
            $this->put(route('student.prayer-logs.update', absolute: false), [
                'date' => $date,
                'prayer' => $prayer,
                'completed' => true,
            ])->assertRedirect();
        }
        $this->put(route('student.prayer-logs.update', absolute: false), [
            'date' => $date,
            'prayer' => 'tahajud',
            'completed' => true,
        ])->assertSessionHasErrors('prayer');
        $this->put(route('student.prayer-logs.update', absolute: false), [
            'date' => $date,
            'prayer' => 'subuh',
            'completed' => true,
        ])->assertRedirect();

        $this->assertSame(5, PrayerLog::query()->where('user_id', $student->id)->whereDate('prayer_date', $date)->count());
        $this->assertEqualsCanonicalizing(
            PrayerLog::PRAYERS,
            PrayerLog::query()->where('user_id', $student->id)->whereDate('prayer_date', $date)->pluck('prayer')->all(),
        );
        $this->get(route('student.daily-rhythm', ['date' => $date], absolute: false))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('student/DailyRhythm')
                ->has('prayers', 5)
                ->where('summary.prayersCompleted', 5)
                ->where('summary.total', 8));
        $this->get(route('student.daily-rhythm', ['date' => '2026-09-28'], absolute: false))
            ->assertInertia(fn ($page) => $page
                ->where('prayers.0.completed', false)
                ->where('summary.prayersCompleted', 0));
    }

    public function test_article_completion_is_membership_scoped_idempotent_and_locks_that_dates_plan(): void
    {
        $tutor = $this->userWithRole(User::ROLE_TUTOR);
        $student = $this->userWithRole(User::ROLE_STUDENT);
        $outsider = $this->userWithRole(User::ROLE_STUDENT);
        $class = $this->classFor($tutor, $student);
        $article = $this->publishedMaterial($tutor, Material::TYPE_ARTICLE, 'Artikel hari ini');
        $plan = $this->planFor($class, $tutor, '2026-09-27');
        $slot = $plan->materials()->create([
            'material_id' => $article->id,
            'tutor_id' => $tutor->id,
            'type' => Material::TYPE_ARTICLE,
            'position' => 1,
        ]);

        $this->actingAs($outsider)
            ->put(route('student.daily-plan-materials.complete', $slot, absolute: false))
            ->assertForbidden();
        $this->actingAs($student)
            ->put(route('student.daily-plan-materials.complete', $slot, absolute: false))
            ->assertRedirect();
        $this->put(route('student.daily-plan-materials.complete', $slot, absolute: false))->assertRedirect();
        $this->assertSame(1, MaterialCompletion::query()->where('user_id', $student->id)->where('daily_plan_material_id', $slot->id)->count());

        $replacement = $this->publishedMaterial($tutor, Material::TYPE_ARTICLE, 'Artikel pengganti');
        $this->actingAs($tutor)->post(route('tutor.daily-plans.store', absolute: false), [
            'class_id' => $class->id,
            'date' => '2026-09-27',
            'target_minutes' => 45,
            'materials' => [
                ['type' => 'article', 'material_id' => $replacement->id],
                ['type' => 'video', 'material_id' => $this->publishedMaterial($tutor, Material::TYPE_VIDEO, 'Video')->id],
                ['type' => 'quiz', 'material_id' => $this->publishedMaterial($tutor, Material::TYPE_QUIZ, 'Kuis')->id],
            ],
        ])->assertSessionHasErrors('plan');
        $this->assertSame($article->id, $slot->fresh()->material_id);
    }

    public function test_quiz_completion_requires_a_matching_current_assignment_and_is_scoped_per_dated_slot(): void
    {
        $tutor = $this->userWithRole(User::ROLE_TUTOR);
        $student = $this->userWithRole(User::ROLE_STUDENT);
        $class = $this->classFor($tutor, $student);
        $quiz = $this->publishedQuiz($tutor);
        $oldSlot = $this->slotFor($this->planFor($class, $tutor, '2026-09-27'), $tutor, $quiz, Material::TYPE_QUIZ, 3);
        $todayPlan = $this->planFor($class, $tutor, today()->toDateString());
        $todaySlot = $this->slotFor($todayPlan, $tutor, $quiz, Material::TYPE_QUIZ, 3);
        $article = $this->publishedMaterial($tutor, Material::TYPE_ARTICLE, 'Artikel');
        $articleSlot = $this->slotFor($todayPlan, $tutor, $article, Material::TYPE_ARTICLE, 1);
        $video = $this->publishedMaterial($tutor, Material::TYPE_VIDEO, 'Video');
        $videoSlot = $this->slotFor($todayPlan, $tutor, $video, Material::TYPE_VIDEO, 2);
        $question = $quiz->quizQuestions()->firstOrFail();
        $choice = $question->choices()->where('is_correct', true)->firstOrFail();
        $this->actingAs($student);

        $this->get(route('student.daily-rhythm', ['date' => today()->toDateString(), 'class_id' => $class->id], absolute: false))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('plan.materials.0.href', route('student.materials.show', ['material' => $article->id, 'assignment' => $articleSlot->id], absolute: false))
                ->where('plan.materials.1.href', route('student.materials.show', ['material' => $video->id, 'assignment' => $videoSlot->id], absolute: false))
                ->where('plan.materials.2.href', route('student.quizzes.show', ['material' => $quiz->id, 'assignment' => $todaySlot->id], absolute: false)));

        $this->put(route('student.daily-plan-materials.complete', $todaySlot, absolute: false))->assertNotFound();
        $this->get(route('student.quizzes.show', ['material' => $quiz->id, 'assignment' => $articleSlot->id], absolute: false))->assertNotFound();
        $this->assertDatabaseCount('material_completions', 0);
        $this->assertDatabaseCount('quiz_attempts', 0);

        foreach ([$oldSlot, $todaySlot] as $slot) {
            $this->post(route('student.quizzes.submit', $quiz, absolute: false), [
                'daily_plan_material_id' => $slot->id,
                'answers' => [['question_id' => $question->id, 'choice_id' => $choice->id]],
            ])->assertRedirect();
        }
        $this->post(route('student.quizzes.submit', $quiz, absolute: false), [
            'daily_plan_material_id' => $todaySlot->id,
            'answers' => [['question_id' => $question->id, 'choice_id' => $choice->id]],
        ])->assertSessionHasErrors('attempt');
        $this->assertSame(2, QuizAttempt::query()->where('student_id', $student->id)->where('material_id', $quiz->id)->count());
        $this->assertSame(2, MaterialCompletion::query()->where('user_id', $student->id)->whereIn('daily_plan_material_id', [$oldSlot->id, $todaySlot->id])->count());

        $foreignClass = $this->classFor($tutor);
        $staleSlot = $this->slotFor($this->planFor($foreignClass, $tutor, today()->toDateString()), $tutor, $quiz, Material::TYPE_QUIZ, 3);
        $this->get(route('student.quizzes.show', ['material' => $quiz->id, 'assignment' => $staleSlot->id], absolute: false))->assertNotFound();
    }

    private function classFor(User $tutor, ?User $student = null): LearningClass
    {
        $class = $tutor->tutorClasses()->create(['name' => 'Kelas '.$tutor->id.' '.uniqid()]);
        if ($student) {
            $class->students()->attach($student->id);
        }

        return $class;
    }

    private function planFor(LearningClass $class, User $tutor, string $date): DailyPlan
    {
        return $class->dailyPlans()->create(['tutor_id' => $tutor->id, 'plan_date' => $date, 'target_minutes' => 45]);
    }

    private function slotFor(DailyPlan $plan, User $tutor, Material $material, string $type, int $position): DailyPlanMaterial
    {
        return $plan->materials()->create([
            'material_id' => $material->id,
            'tutor_id' => $tutor->id,
            'type' => $type,
            'position' => $position,
        ]);
    }

    private function publishedMaterial(User $tutor, string $type, string $title): Material
    {
        return $tutor->materials()->create([
            'type' => $type,
            'title' => $title,
            'status' => Material::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);
    }

    private function publishedQuiz(User $tutor): Material
    {
        $quiz = $this->publishedMaterial($tutor, Material::TYPE_QUIZ, 'Kuis tanggal');
        $question = $quiz->quizQuestions()->create([
            'type' => QuizQuestion::TYPE_CHOICE,
            'prompt' => 'Pilih jawaban yang benar.',
            'points' => 1,
            'position' => 1,
        ]);
        $question->choices()->create(['label' => 'Benar', 'is_correct' => true, 'position' => 1]);
        $question->choices()->create(['label' => 'Salah', 'is_correct' => false, 'position' => 2]);

        return $quiz;
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->forceFill(['role' => $role])->save();

        return $user;
    }
}
