<?php

namespace App\Http\Controllers\Student;

use App\Contracts\StudentMaterialAssignments;
use App\Http\Controllers\Controller;
use App\Http\Requests\Student\SubmitQuizRequest;
use App\Models\Material;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class QuizController extends Controller
{
    public function show(Request $request, Material $material): Response
    {
        Gate::forUser($request->user())->authorize('view', $material);
        abort_unless($material->type === Material::TYPE_QUIZ && $material->isPublished(), 404);

        $questions = $material->quizQuestions()->with(['choices' => fn ($query) => $query->orderBy('position')])->get();
        $attempt = $material->attempts()
            ->where('student_id', $request->user()->getKey())
            ->with(['answers.question'])
            ->first();

        return Inertia::render('student/Quiz', [
            'material' => $material->only(['id', 'type', 'title', 'summary', 'published_at']),
            'questions' => $questions->map(fn (QuizQuestion $question) => [
                'id' => $question->id,
                'type' => $question->type,
                'prompt' => $question->prompt,
                'points' => (float) $question->points,
                'choices' => $question->choices->map(fn ($choice) => [
                    'id' => $choice->id,
                    'label' => $choice->label,
                ])->values(),
            ])->values(),
            'attempt' => $attempt ? $this->studentAttemptData($attempt) : null,
        ]);
    }

    public function submit(SubmitQuizRequest $request, Material $material, StudentMaterialAssignments $assignments): RedirectResponse
    {
        $student = $request->user();
        $data = $request->validated();

        DB::transaction(function () use ($student, $material, $data, $assignments): void {
            $lockedMaterial = Material::query()->whereKey($material->getKey())->lockForUpdate()->firstOrFail();
            abort_unless($lockedMaterial->isPublished() && $assignments->isAssigned($student, $lockedMaterial), 403);
            if ($lockedMaterial->attempts()->where('student_id', $student->getKey())->exists()) {
                throw ValidationException::withMessages(['attempt' => 'Kuis hanya dapat dikirim satu kali.']);
            }

            $questions = $lockedMaterial->quizQuestions()->with('choices')->get();
            $answers = collect($data['answers'])->keyBy('question_id');
            $hasEssay = $questions->contains(fn (QuizQuestion $question) => $question->type === QuizQuestion::TYPE_ESSAY);
            $attempt = $lockedMaterial->attempts()->create([
                'student_id' => $student->getKey(),
                'status' => $hasEssay ? QuizAttempt::STATUS_AWAITING_REVIEW : QuizAttempt::STATUS_COMPLETED,
                'submitted_at' => now(),
                'score' => null,
            ]);
            $automaticScore = 0.0;

            foreach ($questions as $question) {
                $answer = $answers->get($question->getKey());
                if ($question->type === QuizQuestion::TYPE_CHOICE) {
                    $choice = $question->choices->firstWhere('id', (int) $answer['choice_id']);
                    $earned = $choice->is_correct ? (float) $question->points : 0.0;
                    $automaticScore += $earned;
                    $attempt->answers()->create([
                        'question_id' => $question->getKey(),
                        'choice_id' => $choice->getKey(),
                        'score' => $earned,
                    ]);
                } else {
                    $attempt->answers()->create([
                        'question_id' => $question->getKey(),
                        'response' => trim($answer['response']),
                    ]);
                }
            }

            if (! $hasEssay) {
                $attempt->forceFill(['score' => $automaticScore])->save();
            }

        });

        return to_route('student.quizzes.show', $material)->with('status', 'Kuis berhasil dikirim.');
    }

    /** @return array<string, mixed> */
    private function studentAttemptData(QuizAttempt $attempt): array
    {
        return $attempt->only(['id', 'status', 'score', 'submitted_at']) + [
            'answers' => $attempt->answers->map(fn ($answer) => [
                'question_id' => $answer->question_id,
                'choice_id' => $answer->choice_id,
                'response' => $answer->response,
                'score' => $attempt->status === QuizAttempt::STATUS_COMPLETED ? $answer->score : null,
                'comment' => $attempt->status === QuizAttempt::STATUS_COMPLETED ? $answer->reviewer_comment : null,
            ])->values(),
        ];
    }
}
