<?php

namespace App\Http\Requests\Tutor;

use App\Models\Material;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SaveQuizRequest extends FormRequest
{
    public function authorize(): bool
    {
        $material = $this->route('material');

        return $this->user()?->hasRole(User::ROLE_TUTOR)
            && ($material instanceof Material
                ? $material->type === Material::TYPE_QUIZ && $this->user()->can('update', $material)
                : $this->user()->can('create', Material::class));
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'summary' => ['nullable', 'string', 'max:1000'],
            'questions' => ['required', 'array', 'min:1', 'max:50'],
            'questions.*.type' => ['required', 'string', 'in:choice,essay'],
            'questions.*.prompt' => ['required', 'string', 'max:10000'],
            'questions.*.points' => ['required', 'numeric', 'gt:0', 'max:1000'],
            'questions.*.choices' => ['sometimes', 'array', 'max:10'],
            'questions.*.choices.*.label' => ['required', 'string', 'max:2000'],
            'questions.*.choices.*.is_correct' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            foreach ($this->input('questions', []) as $index => $question) {
                if (! is_array($question)) {
                    continue;
                }

                $choices = $question['choices'] ?? [];
                if (($question['type'] ?? null) === 'choice') {
                    if (! is_array($choices) || count($choices) < 2) {
                        $validator->errors()->add("questions.{$index}.choices", 'Soal pilihan memerlukan sedikitnya dua opsi.');

                        continue;
                    }

                    $labels = array_map(static fn ($choice) => is_array($choice) ? trim((string) ($choice['label'] ?? '')) : '', $choices);
                    if (count(array_unique($labels)) < 2) {
                        $validator->errors()->add("questions.{$index}.choices", 'Setiap opsi harus memiliki teks yang berbeda.');
                    }

                    $correctCount = count(array_filter($choices, static fn ($choice) => is_array($choice) && filter_var($choice['is_correct'] ?? false, FILTER_VALIDATE_BOOLEAN)));
                    if ($correctCount !== 1) {
                        $validator->errors()->add("questions.{$index}.choices", 'Pilih tepat satu kunci jawaban.');
                    }
                } elseif ($choices !== []) {
                    $validator->errors()->add("questions.{$index}.choices", 'Soal esai tidak memakai opsi atau kunci otomatis.');
                }
            }
        });
    }
}
