<?php

namespace App\Http\Requests\Student;

use App\Models\Material;
use App\Models\QuizQuestion;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SubmitQuizRequest extends FormRequest
{
    public function authorize(): bool
    {
        $material = $this->route('material');

        return $this->user()?->hasRole(User::ROLE_STUDENT)
            && $material instanceof Material
            && $material->type === Material::TYPE_QUIZ
            && $material->isPublished()
            && $this->user()->can('view', $material);
    }

    public function rules(): array
    {
        return [
            'answers' => ['required', 'array', 'min:1', 'max:50'],
            'answers.*.question_id' => ['required', 'integer', 'distinct'],
            'answers.*.choice_id' => ['nullable', 'integer'],
            'answers.*.response' => ['nullable', 'string', 'max:10000'],
            'answers.*.score' => ['prohibited'],
            'answers.*.is_correct' => ['prohibited'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $material = $this->route('material');
            $answers = $this->input('answers');
            if (! $material instanceof Material || ! is_array($answers)) {
                return;
            }

            $questions = $material->quizQuestions()->with('choices')->get()->keyBy('id');
            if (count($answers) !== $questions->count()) {
                $validator->errors()->add('answers', 'Jawab semua soal sebelum mengirim kuis.');
            }

            $submittedIds = [];
            foreach ($answers as $index => $answer) {
                if (! is_array($answer)) {
                    continue;
                }

                $questionId = filter_var($answer['question_id'] ?? null, FILTER_VALIDATE_INT);
                $question = $questionId === false ? null : $questions->get($questionId);
                if (! $question instanceof QuizQuestion) {
                    $validator->errors()->add("answers.{$index}.question_id", 'Soal ini tidak termasuk dalam kuis.');

                    continue;
                }
                $submittedIds[] = $question->getKey();

                if ($question->type === QuizQuestion::TYPE_CHOICE) {
                    $choiceId = filter_var($answer['choice_id'] ?? null, FILTER_VALIDATE_INT);
                    if ($choiceId === false || ! $question->choices->contains('id', $choiceId)) {
                        $validator->errors()->add("answers.{$index}.choice_id", 'Pilih salah satu opsi untuk soal ini.');
                    }
                    if (isset($answer['response']) && $answer['response'] !== '') {
                        $validator->errors()->add("answers.{$index}.response", 'Jawaban teks hanya berlaku untuk soal esai.');
                    }
                } elseif (! is_string($answer['response'] ?? null) || trim($answer['response']) === '') {
                    $validator->errors()->add("answers.{$index}.response", 'Jawaban esai wajib diisi.');
                } elseif (isset($answer['choice_id']) && $answer['choice_id'] !== null && $answer['choice_id'] !== '') {
                    $validator->errors()->add("answers.{$index}.choice_id", 'Soal esai tidak menerima pilihan jawaban.');
                }
            }

            if (count(array_unique($submittedIds)) !== $questions->count()) {
                $validator->errors()->add('answers', 'Setiap soal harus dijawab tepat satu kali.');
            }
        });
    }
}
