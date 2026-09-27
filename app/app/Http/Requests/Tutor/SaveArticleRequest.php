<?php

namespace App\Http\Requests\Tutor;

use App\Models\Material;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SaveArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $material = $this->route('material');

        return $this->user()?->hasRole(User::ROLE_TUTOR)
            && ($material instanceof Material
                ? $this->user()->can('update', $material)
                : $this->user()->can('create', Material::class));
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'summary' => ['nullable', 'string', 'max:1000'],
            'blocks' => ['required', 'array', 'max:50'],
            'blocks.*' => ['required', 'array'],
            'blocks.*.type' => ['required', 'string', Rule::in(['paragraph', 'heading', 'list', 'quote', 'link'])],
            'blocks.*.text' => ['sometimes', 'string', 'max:10000'],
            'blocks.*.items' => ['sometimes', 'array', 'min:1', 'max:50'],
            'blocks.*.items.*' => ['required', 'string', 'max:1000'],
            'blocks.*.ordered' => ['sometimes', 'boolean'],
            'blocks.*.level' => ['sometimes', 'integer', Rule::in([2, 3])],
            'blocks.*.attribution' => ['sometimes', 'nullable', 'string', 'max:255'],
            'blocks.*.url' => ['sometimes', 'string', 'max:2048'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $blocks = $this->input('blocks', []);
            if (! is_array($blocks)) {
                return;
            }

            $allowedKeys = [
                'paragraph' => ['type', 'text'],
                'heading' => ['type', 'text', 'level'],
                'list' => ['type', 'items', 'ordered'],
                'quote' => ['type', 'text', 'attribution'],
                'link' => ['type', 'text', 'url'],
            ];
            $totalTextBytes = 0;

            foreach ($blocks as $index => $block) {
                if (! is_array($block) || ! isset($block['type']) || ! is_string($block['type']) || ! isset($allowedKeys[$block['type']])) {
                    continue;
                }

                $type = $block['type'];
                $extra = array_diff(array_keys($block), $allowedKeys[$type]);
                if ($extra !== []) {
                    $validator->errors()->add("blocks.$index", 'Blok memuat properti yang tidak didukung.');
                }

                if (in_array($type, ['paragraph', 'heading', 'quote', 'link'], true)
                    && (! isset($block['text']) || ! is_string($block['text']) || trim($block['text']) === '')) {
                    $validator->errors()->add("blocks.$index.text", 'Teks blok wajib diisi.');
                }

                if ($type === 'list' && (! isset($block['items']) || ! is_array($block['items']) || $block['items'] === [])) {
                    $validator->errors()->add("blocks.$index.items", 'Daftar harus memiliki setidaknya satu butir.');
                }

                if ($type === 'link') {
                    $url = $block['url'] ?? null;
                    $parts = is_string($url) ? parse_url($url) : false;
                    if (! is_array($parts) || ! in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true) || empty($parts['host'])) {
                        $validator->errors()->add("blocks.$index.url", 'Tautan harus menggunakan alamat HTTP atau HTTPS yang valid.');
                    }
                }

                $totalTextBytes += strlen(json_encode($block, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '');
            }

            if ($totalTextBytes > 100_000) {
                $validator->errors()->add('blocks', 'Ukuran seluruh isi artikel maksimal 100 KB.');
            }
        });
    }
}
