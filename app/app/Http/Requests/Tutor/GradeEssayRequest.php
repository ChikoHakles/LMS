<?php

namespace App\Http\Requests\Tutor;

use App\Models\QuizAnswer;
use App\Models\QuizQuestion;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class GradeEssayRequest extends FormRequest
{
    public function authorize(): bool
    {
        $answer = $this->route('answer');
        if (! $this->user()?->hasRole(User::ROLE_TUTOR) || ! $answer instanceof QuizAnswer) {
            return false;
        }

        $answer->loadMissing('question.material');

        return $answer->question->type === QuizQuestion::TYPE_ESSAY
            && $this->user()->can('update', $answer->question->material);
    }

    public function rules(): array
    {
        return [
            'score' => ['required', 'numeric', 'min:0'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $answer = $this->route('answer');
            if (! $answer instanceof QuizAnswer) {
                return;
            }

            $answer->loadMissing('question');
            $score = $this->input('score');
            if (is_numeric($score) && (float) $score > (float) $answer->question->points) {
                $validator->errors()->add('score', 'Nilai tidak boleh melebihi poin maksimum soal.');
            }
            if ($answer->graded_at !== null) {
                $validator->errors()->add('score', 'Jawaban esai ini sudah dinilai.');
            }
        });
    }
}
