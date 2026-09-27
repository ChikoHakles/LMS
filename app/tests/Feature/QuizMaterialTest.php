<?php

namespace Tests\Feature;

use App\Contracts\StudentMaterialAssignments;
use App\Models\Material;
use App\Models\QuizQuestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class QuizMaterialTest extends TestCase
{
    use RefreshDatabase;

    public function test_tutor_saves_mixed_questions_in_order_and_owner_can_edit(): void
    {
        $tutor = $this->userWithRole(User::ROLE_TUTOR);
        $this->actingAs($tutor);

        $this->post(route('tutor.materials.quiz.store', absolute: false), $this->quizPayload())
            ->assertRedirect();

        $material = Material::query()->where('title', 'Kuis campuran')->firstOrFail();
        $questions = $material->quizQuestions()->with('choices')->get();
        $this->assertSame($tutor->id, $material->owner_id);
        $this->assertSame(Material::TYPE_QUIZ, $material->type);
        $this->assertSame([0, 1], $questions->pluck('position')->all());
        $this->assertSame([QuizQuestion::TYPE_CHOICE, QuizQuestion::TYPE_ESSAY], $questions->pluck('type')->all());
        $this->assertSame(2.5, (float) $questions[0]->points);
        $this->assertSame(1, $questions[0]->choices->where('is_correct', true)->count());
        $this->assertCount(0, $questions[1]->choices);

        $this->get(route('tutor.materials.quiz.edit', $material, absolute: false))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('tutor/materials/QuizEditor', false)
                ->where('material.questions.0.prompt', 'Berapa hasil dua tambah dua?')
                ->where('material.questions.1.type', QuizQuestion::TYPE_ESSAY)
                ->where('material.questions.0.choices.1.is_correct', true));
    }

    public function test_choice_questions_require_two_distinct_choices_and_exactly_one_key(): void
    {
        $this->actingAs($this->userWithRole(User::ROLE_TUTOR));

        $invalidQuestions = [
            ['type' => 'choice', 'prompt' => 'Terlalu sedikit', 'points' => 1, 'choices' => [['label' => 'Satu', 'is_correct' => true]]],
            ['type' => 'choice', 'prompt' => 'Tanpa kunci', 'points' => 1, 'choices' => [['label' => 'A'], ['label' => 'B']]],
            ['type' => 'choice', 'prompt' => 'Dua kunci', 'points' => 1, 'choices' => [['label' => 'A', 'is_correct' => true], ['label' => 'B', 'is_correct' => true]]],
            ['type' => 'choice', 'prompt' => 'Opsi ganda', 'points' => 1, 'choices' => [['label' => 'Sama', 'is_correct' => true], ['label' => 'Sama']]],
            ['type' => 'essay', 'prompt' => 'Esai tanpa kunci', 'points' => 1, 'choices' => [['label' => 'A']]],
        ];

        foreach ($invalidQuestions as $question) {
            $this->post(route('tutor.materials.quiz.store', absolute: false), [
                'title' => 'Kuis tidak valid',
                'questions' => [$question],
            ])->assertSessionHasErrors('questions.0.choices');
        }

        $this->assertDatabaseCount('materials', 0);
    }

    public function test_non_owner_and_non_tutor_cannot_edit_quiz(): void
    {
        $owner = $this->userWithRole(User::ROLE_TUTOR);
        $otherTutor = $this->userWithRole(User::ROLE_TUTOR);
        $student = $this->userWithRole(User::ROLE_STUDENT);
        $material = $owner->materials()->create([
            'type' => Material::TYPE_QUIZ,
            'title' => 'Kuis milik tutor',
            'status' => Material::STATUS_DRAFT,
        ]);

        $this->actingAs($otherTutor)
            ->put(route('tutor.materials.quiz.update', $material, absolute: false), $this->quizPayload())
            ->assertForbidden();
        $this->actingAs($student)
            ->post(route('tutor.materials.quiz.store', absolute: false), $this->quizPayload())
            ->assertForbidden();
    }

    public function test_student_quiz_access_fails_closed_until_assigned_and_never_receives_keys(): void
    {
        $tutor = $this->userWithRole(User::ROLE_TUTOR);
        $student = $this->userWithRole(User::ROLE_STUDENT);
        $material = $tutor->materials()->create([
            'type' => Material::TYPE_QUIZ,
            'title' => 'Kuis terbit',
            'status' => Material::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);
        $question = $material->quizQuestions()->create([
            'type' => QuizQuestion::TYPE_CHOICE,
            'prompt' => 'Pilih jawaban',
            'points' => 2,
            'position' => 0,
        ]);
        $question->choices()->createMany([
            ['label' => 'Benar', 'is_correct' => true, 'position' => 0],
            ['label' => 'Salah', 'is_correct' => false, 'position' => 1],
        ]);

        $this->actingAs($student);
        $this->get(route('student.quizzes.show', $material, absolute: false))->assertForbidden();

        $this->app->instance(StudentMaterialAssignments::class, new AssignedQuizMaterial($student->id, $material->id));
        $this->get(route('student.quizzes.show', $material, absolute: false))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('student/Quiz', false)
                ->where('questions.0.choices.0.label', 'Benar')
                ->missing('questions.0.choices.0.is_correct')
                ->missing('questions.0.choices.1.is_correct'));
    }

    /** @return array<string, mixed> */
    private function quizPayload(): array
    {
        return [
            'title' => 'Kuis campuran',
            'summary' => 'Dua jenis soal.',
            'questions' => [
                [
                    'type' => 'choice',
                    'prompt' => 'Berapa hasil dua tambah dua?',
                    'points' => 2.5,
                    'choices' => [
                        ['label' => 'Tiga', 'is_correct' => false],
                        ['label' => 'Empat', 'is_correct' => true],
                    ],
                ],
                [
                    'type' => 'essay',
                    'prompt' => 'Jelaskan caranya.',
                    'points' => 5,
                    'choices' => [],
                ],
            ],
        ];
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->forceFill(['role' => $role])->save();

        return $user;
    }
}

final class AssignedQuizMaterial implements StudentMaterialAssignments
{
    public function __construct(private readonly int $studentId, private readonly int $materialId) {}

    public function materialIdsFor(User $student): Collection
    {
        return $student->id === $this->studentId ? collect([$this->materialId]) : collect();
    }

    public function isAssigned(User $student, Material $material): bool
    {
        return $student->id === $this->studentId && $material->id === $this->materialId;
    }
}
