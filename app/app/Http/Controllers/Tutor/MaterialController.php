<?php

namespace App\Http\Controllers\Tutor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tutor\SaveArticleRequest;
use App\Http\Requests\Tutor\SaveVideoRequest;
use App\Models\Material;
use App\Services\ArticleBlockSanitizer;
use App\Support\YouTubeVideoUrl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class MaterialController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        Gate::forUser($user)->authorize('viewAny', Material::class);

        $type = $request->query('type');
        $status = $request->query('status');
        $search = trim((string) $request->query('q', ''));

        $materials = Material::query()
            ->ownedBy($user)
            ->when(in_array($type, Material::TYPES, true), fn ($query) => $query->where('type', $type))
            ->when(in_array($status, [Material::STATUS_DRAFT, Material::STATUS_PUBLISHED], true), fn ($query) => $query->where('status', $status))
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('title', 'like', '%'.$search.'%')
                    ->orWhere('summary', 'like', '%'.$search.'%');
            }))
            ->latest('updated_at')
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('tutor/materials/Index', [
            'materials' => $materials,
            'filters' => ['q' => $search, 'type' => $type, 'status' => $status],
            'types' => Material::TYPES,
        ]);
    }

    public function createArticle(): Response
    {
        Gate::authorize('create', Material::class);

        return Inertia::render('tutor/materials/ArticleEditor', [
            'material' => null,
        ]);
    }

    public function createVideo(): Response
    {
        Gate::authorize('create', Material::class);

        return Inertia::render('tutor/materials/VideoEditor', ['material' => null]);
    }

    public function editVideo(Material $material): Response
    {
        Gate::authorize('update', $material);
        abort_unless($material->type === Material::TYPE_VIDEO, 404);

        return Inertia::render('tutor/materials/VideoEditor', [
            'material' => $material->only(['id', 'type', 'title', 'summary', 'status', 'published_at', 'youtube_video_id']),
        ]);
    }

    public function storeVideo(SaveVideoRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $material = new Material;
        $material->forceFill([
            'type' => Material::TYPE_VIDEO,
            'title' => $data['title'],
            'summary' => $data['summary'] ?? null,
            'youtube_video_id' => YouTubeVideoUrl::extractId($data['youtube_url']),
            'status' => Material::STATUS_DRAFT,
        ]);
        $material->owner()->associate($request->user());
        $material->save();

        return to_route('tutor.materials.video.edit', $material)->with('status', 'Draf video berhasil disimpan.');
    }

    public function updateVideo(SaveVideoRequest $request, Material $material): RedirectResponse
    {
        $data = $request->validated();
        $material->forceFill([
            'title' => $data['title'],
            'summary' => $data['summary'] ?? null,
            'youtube_video_id' => YouTubeVideoUrl::extractId($data['youtube_url']),
            'status' => Material::STATUS_DRAFT,
            'published_at' => null,
        ])->save();

        return to_route('tutor.materials.video.edit', $material)->with('status', 'Draf video berhasil diperbarui.');
    }

    public function publishVideo(Material $material): RedirectResponse
    {
        Gate::authorize('publish', $material);
        abort_unless($material->type === Material::TYPE_VIDEO, 404);

        if (trim($material->title) === '' || $material->youtubeEmbedUrl() === null) {
            throw ValidationException::withMessages([
                'youtube_url' => 'Tambahkan tautan YouTube yang valid sebelum menerbitkan video.',
            ]);
        }

        $material->forceFill([
            'status' => Material::STATUS_PUBLISHED,
            'published_at' => now(),
        ])->save();

        return to_route('tutor.materials.video.edit', $material)->with('status', 'Video berhasil diterbitkan.');
    }

    public function edit(Material $material): Response
    {
        Gate::authorize('update', $material);
        abort_unless($material->type === Material::TYPE_ARTICLE, 404);

        return Inertia::render('tutor/materials/ArticleEditor', [
            'material' => $material->only(['id', 'type', 'title', 'summary', 'status', 'published_at', 'blocks']),
        ]);
    }

    public function show(Material $material): Response
    {
        Gate::authorize('view', $material);
        abort_unless($material->type === Material::TYPE_ARTICLE, 404);

        return Inertia::render('materials/Show', [
            'material' => $material->only(['id', 'type', 'title', 'summary', 'status', 'published_at', 'blocks']),
            'canEdit' => true,
        ]);
    }

    public function store(SaveArticleRequest $request, ArticleBlockSanitizer $sanitizer): RedirectResponse
    {
        $data = $request->validated();
        $material = new Material;
        $material->forceFill([
            'type' => Material::TYPE_ARTICLE,
            'title' => $data['title'],
            'summary' => $data['summary'] ?? null,
            'status' => Material::STATUS_DRAFT,
            'blocks' => $sanitizer->sanitize($data['blocks']),
        ]);
        $material->owner()->associate($request->user());
        $material->save();

        return to_route('tutor.materials.edit', $material)->with('status', 'Draf artikel berhasil disimpan.');
    }

    public function update(SaveArticleRequest $request, Material $material, ArticleBlockSanitizer $sanitizer): RedirectResponse
    {
        abort_unless($material->type === Material::TYPE_ARTICLE, 404);
        $data = $request->validated();
        $material->forceFill([
            'title' => $data['title'],
            'summary' => $data['summary'] ?? null,
            'blocks' => $sanitizer->sanitize($data['blocks']),
            'status' => Material::STATUS_DRAFT,
            'published_at' => null,
        ])->save();

        return to_route('tutor.materials.edit', $material)->with('status', 'Draf artikel berhasil disimpan.');
    }

    public function publish(Material $material): RedirectResponse
    {
        Gate::authorize('publish', $material);
        abort_unless($material->type === Material::TYPE_ARTICLE, 404);

        if (! $this->hasPublishableContent($material)) {
            throw ValidationException::withMessages([
                'blocks' => 'Tambahkan setidaknya satu blok berisi teks sebelum menerbitkan artikel.',
            ]);
        }

        $material->forceFill([
            'status' => Material::STATUS_PUBLISHED,
            'published_at' => now(),
        ])->save();

        return to_route('tutor.materials.edit', $material)->with('status', 'Artikel berhasil diterbitkan.');
    }

    private function hasPublishableContent(Material $material): bool
    {
        if (trim($material->title) === '' || ! is_array($material->blocks) || $material->blocks === []) {
            return false;
        }

        foreach ($material->blocks as $block) {
            if (isset($block['text']) && trim((string) $block['text']) !== '') {
                return true;
            }

            if (isset($block['items']) && collect($block['items'])->contains(fn ($item) => trim((string) $item) !== '')) {
                return true;
            }
        }

        return false;
    }
}
