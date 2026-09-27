<?php

namespace App\Http\Controllers\Tutor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tutor\SaveQuizRequest;
use App\Models\Material;
use App\Models\QuizQuestion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class QuizMaterialController extends Controller
{
    public function create(): Response
    {
        Gate::authorize('create', Material::class);

        return Inertia::render('tutor/materials/QuizEditor', ['material' => null]);
    }

    public function edit(Material $material): Response
    {
        Gate::authorize('update', $material);
        abort_unless($material->type === Material::TYPE_QUIZ, 404);

        return Inertia::render('tutor/materials/QuizEditor', [
            'material' => $this->editorData($material),
        ]);
    }

    public function store(SaveQuizRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $material = DB::transaction(function () use ($request, $data): Material {
            $material = new Material;
            $material->forceFill([
                'type' => Material::TYPE_QUIZ,
                'title' => $data['title'],
                'summary' => $data['summary'] ?? null,
                'status' => Material::STATUS_DRAFT,
            ]);
            $material->owner()->associate($request->user());
            $material->save();
            $this->replaceQuestions($material, $data['questions']);

            return $material;
        });

        return to_route('tutor.materials.quiz.edit', $material)->with('status', 'Draf kuis berhasil disimpan.');
    }

    public function update(SaveQuizRequest $request, Material $material): RedirectResponse
    {
        abort_unless($material->type === Material::TYPE_QUIZ, 404);
        $data = $request->validated();
        DB::transaction(function () use ($material, $data): void {
            $lockedMaterial = Material::query()->whereKey($material->getKey())->lockForUpdate()->firstOrFail();
            if ($lockedMaterial->attempts()->exists()) {
                throw ValidationException::withMessages(['questions' => 'Kuis tidak dapat diubah setelah ada siswa mengirim jawaban.']);
            }

            $lockedMaterial->forceFill([
                'title' => $data['title'],
                'summary' => $data['summary'] ?? null,
                'status' => Material::STATUS_DRAFT,
                'published_at' => null,
            ])->save();
            $this->replaceQuestions($lockedMaterial, $data['questions']);
        });

        return to_route('tutor.materials.quiz.edit', $material)->with('status', 'Draf kuis berhasil diperbarui.');
    }

    public function publish(Material $material): RedirectResponse
    {
        Gate::authorize('publish', $material);
        abort_unless($material->type === Material::TYPE_QUIZ, 404);
        DB::transaction(function () use ($material): void {
            $lockedMaterial = Material::query()->whereKey($material->getKey())->lockForUpdate()->firstOrFail();
            if ($lockedMaterial->attempts()->exists()) {
                throw ValidationException::withMessages(['quiz' => 'Kuis dengan jawaban siswa tidak dapat diterbitkan ulang.']);
            }

            $questions = $lockedMaterial->quizQuestions()->with('choices')->get();
            $valid = $questions->isNotEmpty() && $questions->every(fn (QuizQuestion $question) => trim($question->prompt) !== ''
                && (float) $question->points > 0
                && ($question->type === QuizQuestion::TYPE_ESSAY
                    ? $question->choices->isEmpty()
                    : $question->type === QuizQuestion::TYPE_CHOICE
                        && $question->choices->count() >= 2
                        && $question->choices->where('is_correct', true)->count() === 1));

            if (! $valid) {
                throw ValidationException::withMessages(['questions' => 'Lengkapi semua soal dan kunci jawaban sebelum menerbitkan kuis.']);
            }

            $lockedMaterial->forceFill(['status' => Material::STATUS_PUBLISHED, 'published_at' => now()])->save();
        });

        return to_route('tutor.materials.quiz.edit', $material)->with('status', 'Kuis berhasil diterbitkan.');
    }

    /** @param array<int, array<string, mixed>> $questions */
    private function replaceQuestions(Material $material, array $questions): void
    {
        $material->quizQuestions()->delete();
        foreach (array_values($questions) as $position => $data) {
            $question = $material->quizQuestions()->create([
                'type' => $data['type'],
                'prompt' => $data['prompt'],
                'points' => $data['points'],
                'position' => $position,
            ]);

            if ($data['type'] === QuizQuestion::TYPE_CHOICE) {
                foreach (array_values($data['choices']) as $choicePosition => $choice) {
                    $question->choices()->create([
                        'label' => $choice['label'],
                        'is_correct' => (bool) ($choice['is_correct'] ?? false),
                        'position' => $choicePosition,
                    ]);
                }
            }
        }
    }

    /** @return array<string, mixed> */
    private function editorData(Material $material): array
    {
        return $material->only(['id', 'type', 'title', 'summary', 'status', 'published_at']) + [
            'questions' => $material->quizQuestions()->with('choices')->orderBy('position')->get()->map(fn (QuizQuestion $question) => [
                'id' => $question->id,
                'type' => $question->type,
                'prompt' => $question->prompt,
                'points' => (float) $question->points,
                'choices' => $question->choices->map(fn ($choice) => [
                    'label' => $choice->label,
                    'is_correct' => $choice->is_correct,
                ])->values(),
            ])->values(),
        ];
    }
}
