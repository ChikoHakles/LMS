<?php

namespace App\Http\Requests\Tutor;

use App\Models\Material;
use App\Models\User;
use App\Support\YouTubeVideoUrl;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SaveVideoRequest extends FormRequest
{
    public function authorize(): bool
    {
        $material = $this->route('material');

        return $this->user()?->hasRole(User::ROLE_TUTOR)
            && ($material instanceof Material
                ? $material->type === Material::TYPE_VIDEO && $this->user()->can('update', $material)
                : $this->user()->can('create', Material::class));
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'summary' => ['nullable', 'string', 'max:1000'],
            'youtube_url' => ['required', 'string', 'max:2048'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $url = $this->input('youtube_url');
            if (is_string($url) && YouTubeVideoUrl::extractId($url) === null) {
                $validator->errors()->add('youtube_url', 'Gunakan tautan YouTube watch, youtu.be, atau Shorts yang valid.');
            }
        });
    }
}
