<?php

namespace Tests\Feature;

use App\Models\DailyPlanMaterial;
use App\Models\LearningClass;
use App\Models\Material;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentQuizJourneyTest extends TestCase
{
    use RefreshDatabase;

    public function test_tutor_creates_assigns_and_grades_quiz_then_student_sees_score_per_assignment(): void
    {
        $tutor = $this->user(User::ROLE_TUTOR);
        $student = $this->user(User::ROLE_STUDENT);
        $outsider = $this->user(User::ROLE_STUDENT);

        $this->actingAs($tutor)->post(route('tutor.materials.quiz.store', absolute: false), [
            'title' => 'Kuis ekosistem',
            'summary' => 'Pilihan dan esai',
            'questions' => [
                ['type' => 'choice', 'prompt' => 'Pilih produsen', 'points' => 2, 'choices' => [
                    ['label' => 'Rumput', 'is_correct' => true],
                    ['label' => 'Harimau', 'is_correct' => false],
                ]],
                ['type' => 'essay', 'prompt' => 'Jelaskan rantai makanan', 'points' => 3, 'choices' => []],
            ],
        ])->assertRedirect();
        $quiz = Material::query()->where('title', 'Kuis ekosistem')->firstOrFail();
        $this->patch(route('tutor.materials.quiz.publish', $quiz, absolute: false))->assertRedirect();

        $this->post(route('tutor.classes.store', absolute: false), ['name' => 'Kelas quiz'])->assertRedirect();
        $class = LearningClass::query()->where('name', 'Kelas quiz')->firstOrFail();
        $this->put(route('tutor.classes.students.update', $class, absolute: false), ['student_ids' => [$student->id]])->assertRedirect();

        $article = $tutor->materials()->create(['type' => Material::TYPE_ARTICLE, 'title' => 'Artikel', 'status' => Material::STATUS_PUBLISHED, 'published_at' => now()]);
        $video = $tutor->materials()->create(['type' => Material::TYPE_VIDEO, 'title' => 'Video', 'status' => Material::STATUS_PUBLISHED, 'published_at' => now()]);
        foreach ([today()->subDay()->toDateString(), today()->toDateString()] as $date) {
            $this->post(route('tutor.daily-plans.store', absolute: false), [
                'class_id' => $class->id,
                'date' => $date,
                'target_minutes' => 30,
                'materials' => [
                    ['type' => 'article', 'material_id' => $article->id],
                    ['type' => 'video', 'material_id' => $video->id],
                    ['type' => 'quiz', 'material_id' => $quiz->id],
                ],
            ])->assertRedirect();
        }
        $assignments = DailyPlanMaterial::query()->where('material_id', $quiz->id)->orderBy('id')->get();
        $this->assertCount(2, $assignments);

        $this->actingAs($outsider)->get(route('student.quizzes.index', absolute: false))
            ->assertInertia(fn ($page) => $page->component('student/Quizzes/Index', false)->has('quizzes.data', 0));
        $this->get(route('student.quizzes.show', ['material' => $quiz->id, 'assignment' => $assignments[0]->id], false))->assertForbidden();

        $this->actingAs($student)->get(route('student.quizzes.index', absolute: false))
            ->assertInertia(fn ($page) => $page->component('student/Quizzes/Index', false)
                ->has('quizzes.data', 2)
                ->where('quizzes.data.0.status', null)
                ->missing('quizzes.data.0.is_correct'));
        $this->get(route('student.quizzes.show', $quiz, absolute: false))
            ->assertRedirect(route('student.quizzes.index', ['material' => $quiz->id], false));

        $questions = $quiz->quizQuestions()->get();
        $correctChoice = $questions[0]->choices()->where('is_correct', true)->firstOrFail();
        $this->post(route('student.quizzes.submit', $quiz, absolute: false), [
            'answers' => [
                ['question_id' => $questions[0]->id, 'choice_id' => $correctChoice->id],
                ['question_id' => $questions[1]->id, 'response' => 'Rumput dimakan herbivor.'],
            ],
        ])->assertSessionHasErrors('daily_plan_material_id');
        $this->post(route('student.quizzes.submit', $quiz, absolute: false), [
            'daily_plan_material_id' => $assignments[0]->id,
            'answers' => [
                ['question_id' => $questions[0]->id, 'choice_id' => $correctChoice->id],
                ['question_id' => $questions[1]->id, 'response' => 'Rumput dimakan herbivor.'],
            ],
        ])->assertRedirect();
        $attempt = QuizAttempt::query()->where('daily_plan_material_id', $assignments[0]->id)->sole();
        $this->assertSame(QuizAttempt::STATUS_AWAITING_REVIEW, $attempt->status);
        $this->assertDatabaseHas('material_completions', ['daily_plan_material_id' => $assignments[0]->id, 'user_id' => $student->id]);

        $this->get(route('student.quizzes.index', absolute: false))
            ->assertInertia(fn ($page) => $page->where('quizzes.data.1.status', QuizAttempt::STATUS_AWAITING_REVIEW)
                ->where('quizzes.data.1.score', null)
                ->where('quizzes.data.0.status', null));

        $essay = $attempt->answers()->where('question_id', $questions[1]->id)->sole();
        $this->actingAs($tutor)->patch(route('tutor.essays.grade', $essay, absolute: false), ['score' => 2.5, 'comment' => 'Baik'])->assertRedirect();
        $this->actingAs($student)->get(route('student.quizzes.index', absolute: false))
            ->assertInertia(fn ($page) => $page->where('quizzes.data.1.status', QuizAttempt::STATUS_COMPLETED)
                ->where('quizzes.data.1.score', '4.50')
                ->where('quizzes.data.0.score', null));
        $this->get(route('student.quizzes.show', ['material' => $quiz->id, 'assignment' => $assignments[0]->id], false))
            ->assertInertia(fn ($page) => $page->where('attempt.score', '4.50')->where('attempt.answers.1.comment', 'Baik'));
    }

    public function test_future_assignment_is_listed_but_cannot_be_submitted(): void
    {
        $tutor = $this->user(User::ROLE_TUTOR);
        $student = $this->user(User::ROLE_STUDENT);
        $class = new LearningClass(['name' => 'Kelas besok']);
        $class->tutor()->associate($tutor);
        $class->save();
        $class->students()->attach($student);
        $quiz = $tutor->materials()->create(['type' => Material::TYPE_QUIZ, 'title' => 'Kuis besok', 'status' => Material::STATUS_PUBLISHED, 'published_at' => now()]);
        $question = $quiz->quizQuestions()->create(['type' => 'choice', 'prompt' => 'Pilih satu', 'points' => 1, 'position' => 0]);
        $choice = $question->choices()->create(['label' => 'Ya', 'is_correct' => true, 'position' => 0]);
        $plan = $class->dailyPlans()->create(['tutor_id' => $tutor->id, 'plan_date' => today()->addDay(), 'target_minutes' => 30]);
        $assignment = $plan->materials()->create(['material_id' => $quiz->id, 'tutor_id' => $tutor->id, 'type' => Material::TYPE_QUIZ, 'position' => 3]);

        $this->actingAs($student)->get(route('student.quizzes.index', absolute: false))
            ->assertInertia(fn ($page) => $page->where('quizzes.data.0.available', false));
        $this->get(route('student.quizzes.show', ['material' => $quiz->id, 'assignment' => $assignment->id], false))
            ->assertInertia(fn ($page) => $page->where('canSubmit', false));
        $this->post(route('student.quizzes.submit', $quiz, absolute: false), [
            'daily_plan_material_id' => $assignment->id,
            'answers' => [['question_id' => $question->id, 'choice_id' => $choice->id]],
        ])->assertForbidden();
        $this->assertDatabaseCount('quiz_attempts', 0);
    }

    private function user(string $role): User
    {
        $user = User::factory()->create();
        $user->forceFill(['role' => $role])->save();

        return $user;
    }
}
