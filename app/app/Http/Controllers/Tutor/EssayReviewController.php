<?php

namespace App\Http\Controllers\Tutor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tutor\GradeEssayRequest;
use App\Models\Material;
use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class EssayReviewController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Material::class);
        $tutor = $request->user();

        $pending = QuizAnswer::query()
            ->whereNull('graded_at')
            ->whereHas('question', fn ($query) => $query->where('type', QuizQuestion::TYPE_ESSAY))
            ->whereHas('attempt', fn ($query) => $query
                ->where('status', QuizAttempt::STATUS_AWAITING_REVIEW)
                ->whereHas('material', fn ($query) => $query->where('owner_id', $tutor->getKey())))
            ->with(['question.material', 'attempt.student'])
            ->orderBy('created_at')
            ->get()
            ->map(fn (QuizAnswer $answer) => [
                'id' => $answer->id,
                'attempt_id' => $answer->attempt_id,
                'student' => ['name' => $answer->attempt->student->name],
                'material' => ['id' => $answer->question->material->id, 'title' => $answer->question->material->title],
                'question' => ['id' => $answer->question->id, 'prompt' => $answer->question->prompt, 'points' => (float) $answer->question->points],
                'response' => $answer->response,
                'submitted_at' => $answer->attempt->submitted_at,
            ])->values();

        return Inertia::render('tutor/EssayReview', ['pendingAnswers' => $pending]);
    }

    public function grade(GradeEssayRequest $request, QuizAnswer $answer): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($request, $answer, $data): void {
            $answer = QuizAnswer::query()->whereKey($answer->getKey())->lockForUpdate()->firstOrFail();
            if ($answer->graded_at !== null) {
                throw ValidationException::withMessages(['score' => 'Jawaban esai ini sudah dinilai.']);
            }
            $answer->load(['question', 'attempt.answers.question']);
            $answer->forceFill([
                'score' => $data['score'],
                'reviewer_comment' => $data['comment'] ?? null,
                'graded_by' => $request->user()->getKey(),
                'graded_at' => now(),
            ])->save();

            $attempt = $answer->attempt;
            $attempt->unsetRelation('answers');
            $answers = $attempt->answers()->with('question')->get();
            $allEssaysGraded = $answers->every(fn (QuizAnswer $item) => $item->question->type !== QuizQuestion::TYPE_ESSAY || $item->graded_at !== null);

            if ($allEssaysGraded) {
                $attempt->forceFill([
                    'status' => QuizAttempt::STATUS_COMPLETED,
                    'score' => $answers->sum(fn (QuizAnswer $item) => (float) ($item->score ?? 0)),
                ])->save();
            }
        });

        return back()->with('status', 'Jawaban esai berhasil dinilai.');
    }
}
