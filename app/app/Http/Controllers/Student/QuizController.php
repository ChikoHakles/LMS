<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\QuizQuestion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class QuizController extends Controller
{
    public function show(Request $request, Material $material): Response
    {
        Gate::forUser($request->user())->authorize('view', $material);
        abort_unless($material->type === Material::TYPE_QUIZ && $material->isPublished(), 404);

        $questions = $material->quizQuestions()->with(['choices' => fn ($query) => $query->orderBy('position')])->get();

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
            'attempt' => null,
        ]);
    }
}
