<?php

namespace Tests\Feature;

use App\Contracts\StudentMaterialAssignments;
use App\Models\Material;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class QuizSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_choice_score_is_server_calculated_and_attempt_can_only_be_submitted_once(): void
    {
        $tutor = $this->userWithRole(User::ROLE_TUTOR);
        $student = $this->userWithRole(User::ROLE_STUDENT);
        $anotherStudent = $this->userWithRole(User::ROLE_STUDENT);
        $material = $this->publishedQuiz($tutor, [[
            'type' => QuizQuestion::TYPE_CHOICE,
            'prompt' => 'Pilih jawaban benar.',
            'points' => 4,
            'choices' => [['Benar', true], ['Salah', false]],
        ]]);
        $question = $material->quizQuestions()->firstOrFail();
        $correct = $question->choices()->where('is_correct', true)->firstOrFail();
        $this->app->instance(StudentMaterialAssignments::class, new AssignedQuizMaterials([$student->id => [$material->id]]));
        $this->actingAs($student);

        $this->post(route('student.quizzes.submit', $material, absolute: false), [
            'student_id' => $anotherStudent->id,
            'answers' => [['question_id' => $question->id, 'choice_id' => $correct->id, 'score' => 100, 'is_correct' => false]],
        ])->assertSessionHasErrors(['student_id', 'answers.0.score', 'answers.0.is_correct']);
        $this->assertDatabaseCount('quiz_attempts', 0);

        $this->post(route('student.quizzes.submit', $material, absolute: false), [
            'answers' => [['question_id' => $question->id, 'choice_id' => $correct->id]],
        ])->assertRedirect(route('student.quizzes.show', $material, absolute: false));

        $attempt = QuizAttempt::query()->sole();
        $this->assertSame($student->id, $attempt->student_id);
        $this->assertSame(QuizAttempt::STATUS_COMPLETED, $attempt->status);
        $this->assertSame('4.00', $attempt->score);
        $this->assertSame($correct->id, $attempt->answers()->sole()->choice_id);
        $this->assertSame('4.00', $attempt->answers()->sole()->score);

        $this->post(route('student.quizzes.submit', $material, absolute: false), [
            'answers' => [['question_id' => $question->id, 'choice_id' => $correct->id]],
        ])->assertSessionHasErrors('attempt');
        $this->assertDatabaseCount('quiz_attempts', 1);

        $this->get(route('student.quizzes.show', $material, absolute: false))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('student/Quiz', false)
                ->where('attempt.status', QuizAttempt::STATUS_COMPLETED)
                ->where('attempt.score', '4.00')
                ->missing('questions.0.choices.0.is_correct'));
    }

    public function test_submission_requires_assignment_every_answer_and_a_choice_from_that_question(): void
    {
        $tutor = $this->userWithRole(User::ROLE_TUTOR);
        $student = $this->userWithRole(User::ROLE_STUDENT);
        $unassigned = $this->userWithRole(User::ROLE_STUDENT);
        $material = $this->publishedQuiz($tutor, [
            ['type' => QuizQuestion::TYPE_CHOICE, 'prompt' => 'Satu?', 'points' => 2, 'choices' => [['A', true], ['B', false]]],
            ['type' => QuizQuestion::TYPE_CHOICE, 'prompt' => 'Dua?', 'points' => 2, 'choices' => [['C', true], ['D', false]]],
        ]);
        $questions = $material->quizQuestions()->with('choices')->get();
        $otherQuiz = $this->publishedQuiz($tutor, [[
            'type' => QuizQuestion::TYPE_CHOICE,
            'prompt' => 'Lain?',
            'points' => 2,
            'choices' => [['E', true], ['F', false]],
        ]]);
        $foreignChoice = $otherQuiz->quizQuestions()->firstOrFail()->choices()->firstOrFail();
        $this->app->instance(StudentMaterialAssignments::class, new AssignedQuizMaterials([$student->id => [$material->id]]));

        $this->actingAs($unassigned)
            ->post(route('student.quizzes.submit', $material, absolute: false), ['answers' => []])
            ->assertForbidden();

        $this->actingAs($student)
            ->post(route('student.quizzes.submit', $material, absolute: false), [
                'answers' => [['question_id' => $questions[0]->id, 'choice_id' => $foreignChoice->id]],
            ])->assertSessionHasErrors(['answers', 'answers.0.choice_id']);
        $this->assertDatabaseCount('quiz_attempts', 0);
    }

    public function test_essay_queue_is_owner_scoped_and_grading_completes_when_all_essays_are_scored(): void
    {
        $owner = $this->userWithRole(User::ROLE_TUTOR);
        $otherTutor = $this->userWithRole(User::ROLE_TUTOR);
        $student = $this->userWithRole(User::ROLE_STUDENT);
        $material = $this->publishedQuiz($owner, [
            ['type' => QuizQuestion::TYPE_ESSAY, 'prompt' => 'Jelaskan A.', 'points' => 5, 'choices' => []],
            ['type' => QuizQuestion::TYPE_ESSAY, 'prompt' => 'Jelaskan B.', 'points' => 3, 'choices' => []],
        ]);
        $questions = $material->quizQuestions()->get();
        $this->app->instance(StudentMaterialAssignments::class, new AssignedQuizMaterials([$student->id => [$material->id]]));
        $this->actingAs($student)
            ->post(route('student.quizzes.submit', $material, absolute: false), [
                'answers' => [
                    ['question_id' => $questions[0]->id, 'response' => 'Jawaban esai pertama'],
                    ['question_id' => $questions[1]->id, 'response' => 'Jawaban esai kedua'],
                ],
            ])->assertRedirect();

        $attempt = QuizAttempt::query()->sole();
        $answers = $attempt->answers()->orderBy('question_id')->get();
        $this->assertSame(QuizAttempt::STATUS_AWAITING_REVIEW, $attempt->status);
        $this->assertNull($attempt->score);
        $this->assertNull($answers[0]->score);

        $this->actingAs($otherTutor)
            ->get(route('tutor.essays.index', absolute: false))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('tutor/EssayReview', false)->has('pendingAnswers', 0));
        $this->actingAs($owner)
            ->get(route('tutor.essays.index', absolute: false))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('tutor/EssayReview', false)
                ->has('pendingAnswers', 2)
                ->where('pendingAnswers.0.student.name', $student->name));

        $first = $answers[0];
        $this->actingAs($otherTutor)
            ->patch(route('tutor.essays.grade', $first, absolute: false), ['score' => 5])
            ->assertForbidden();
        $this->actingAs($student)
            ->patch(route('tutor.essays.grade', $first, absolute: false), ['score' => 5])
            ->assertForbidden();
        $this->actingAs($owner)
            ->patch(route('tutor.essays.grade', $first, absolute: false), ['score' => 6, 'comment' => 'Terlalu tinggi'])
            ->assertSessionHasErrors('score');

        $this->patch(route('tutor.essays.grade', $first, absolute: false), ['score' => 4, 'comment' => 'Uraian jelas.'])
            ->assertRedirect();
        $attempt->refresh();
        $this->assertSame(QuizAttempt::STATUS_AWAITING_REVIEW, $attempt->status);
        $this->assertNull($attempt->score);
        $first->refresh();
        $this->assertSame($owner->id, $first->graded_by);
        $this->assertSame('Uraian jelas.', $first->reviewer_comment);

        $this->patch(route('tutor.essays.grade', $answers[1], absolute: false), ['score' => 2.5, 'comment' => 'Baik.'])
            ->assertRedirect();
        $attempt->refresh();
        $this->assertSame(QuizAttempt::STATUS_COMPLETED, $attempt->status);
        $this->assertSame('6.50', $attempt->score);

        $this->patch(route('tutor.essays.grade', $first, absolute: false), ['score' => 4, 'comment' => 'Nilai ulang'])
            ->assertSessionHasErrors('score');
        $this->assertSame('6.50', $attempt->fresh()->score);
    }

    /** @param list<array{type: string, prompt: string, points: int|float, choices: list<array{string, bool}>}> $questions */
    private function publishedQuiz(User $tutor, array $questions): Material
    {
        $material = $tutor->materials()->create([
            'type' => Material::TYPE_QUIZ,
            'title' => 'Kuis uji',
            'status' => Material::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);
        foreach ($questions as $position => $data) {
            $question = $material->quizQuestions()->create([
                'type' => $data['type'],
                'prompt' => $data['prompt'],
                'points' => $data['points'],
                'position' => $position,
            ]);
            foreach ($data['choices'] as $choicePosition => [$label, $isCorrect]) {
                $question->choices()->create([
                    'label' => $label,
                    'is_correct' => $isCorrect,
                    'position' => $choicePosition,
                ]);
            }
        }

        return $material;
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->forceFill(['role' => $role])->save();

        return $user;
    }
}

final class AssignedQuizMaterials implements StudentMaterialAssignments
{
    /** @param array<int, list<int>> $assignments */
    public function __construct(private readonly array $assignments) {}

    public function materialIdsFor(User $student): Collection
    {
        return collect($this->assignments[$student->id] ?? []);
    }

    public function isAssigned(User $student, Material $material): bool
    {
        return in_array($material->id, $this->assignments[$student->id] ?? [], true);
    }
}
