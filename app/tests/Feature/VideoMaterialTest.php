<?php

namespace Tests\Feature;

use App\Contracts\StudentMaterialAssignments;
use App\Models\Material;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class VideoMaterialTest extends TestCase
{
    use RefreshDatabase;

    public function test_tutor_can_save_allowed_youtube_url_forms_as_video_ids(): void
    {
        $tutor = $this->userWithRole(User::ROLE_TUTOR);
        $this->actingAs($tutor);

        foreach ([
            'https://www.youtube.com/watch?v=abcdefghijk&t=30s' => 'abcdefghijk',
            'https://youtu.be/ZYXWVUTSRQ1?si=share' => 'ZYXWVUTSRQ1',
            'https://www.youtube.com/shorts/a_b-CdEf123' => 'a_b-CdEf123',
        ] as $url => $videoId) {
            $this->post(route('tutor.materials.video.store', absolute: false), [
                'title' => 'Video pelajaran',
                'summary' => 'Ringkasan video.',
                'youtube_url' => $url,
            ])->assertRedirect();

            $material = Material::query()->where('youtube_video_id', $videoId)->firstOrFail();
            $this->assertSame(Material::TYPE_VIDEO, $material->type);
            $this->assertSame($tutor->id, $material->owner_id);
            $this->assertSame(Material::STATUS_DRAFT, $material->status);
            $this->assertSame($videoId, $material->youtube_video_id);
            $this->assertSame('https://www.youtube-nocookie.com/embed/'.$videoId, $material->youtubeEmbedUrl());
        }
    }

    public function test_foreign_hosts_and_malformed_youtube_urls_are_rejected(): void
    {
        $tutor = $this->userWithRole(User::ROLE_TUTOR);
        $this->actingAs($tutor);

        foreach ([
            'https://example.com/watch?v=abcdefghijk',
            'https://youtube.com.attacker.test/watch?v=abcdefghijk',
            'https://youtu.be.attacker.test/abcdefghijk',
            'https://youtube.com/watch?v=too-short',
            'https://youtube.com:443/watch?v=abcdefghijk',
            'javascript:alert(1)',
        ] as $url) {
            $this->post(route('tutor.materials.video.store', absolute: false), [
                'title' => 'Video tidak valid',
                'youtube_url' => $url,
            ])->assertSessionHasErrors('youtube_url');
        }

        $this->assertSame(0, Material::query()->count());
    }

    public function test_owner_can_update_video_link_but_another_tutor_cannot(): void
    {
        $owner = $this->userWithRole(User::ROLE_TUTOR);
        $otherTutor = $this->userWithRole(User::ROLE_TUTOR);
        $video = $owner->materials()->create([
            'type' => Material::TYPE_VIDEO,
            'title' => 'Pelajaran video',
            'summary' => 'Ringkasan lama.',
            'youtube_video_id' => 'abcdefghijk',
            'status' => Material::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);

        $this->actingAs($otherTutor)
            ->put(route('tutor.materials.video.update', $video, absolute: false), [
                'title' => 'Tidak boleh',
                'youtube_url' => 'https://youtu.be/ZYXWVUTSRQ1',
            ])->assertForbidden();

        $this->actingAs($owner)
            ->put(route('tutor.materials.video.update', $video, absolute: false), [
                'title' => 'Pelajaran video diperbarui',
                'summary' => 'Ringkasan baru.',
                'youtube_url' => 'https://youtu.be/ZYXWVUTSRQ1',
            ])->assertRedirect();

        $this->assertSame('Pelajaran video diperbarui', $video->fresh()->title);
        $this->assertSame('ZYXWVUTSRQ1', $video->fresh()->youtube_video_id);
        $this->assertSame(Material::STATUS_DRAFT, $video->fresh()->status);
        $this->assertNull($video->fresh()->published_at);
    }

    public function test_student_video_player_requires_a_published_assigned_material(): void
    {
        $tutor = $this->userWithRole(User::ROLE_TUTOR);
        $student = $this->userWithRole(User::ROLE_STUDENT);
        $published = $tutor->materials()->create([
            'type' => Material::TYPE_VIDEO,
            'title' => 'Video ditugaskan',
            'youtube_video_id' => 'abcdefghijk',
            'status' => Material::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);
        $draft = $tutor->materials()->create([
            'type' => Material::TYPE_VIDEO,
            'title' => 'Video draf',
            'youtube_video_id' => 'ZYXWVUTSRQ1',
        ]);

        $this->actingAs($student);
        $this->get(route('student.materials.show', $published, absolute: false))->assertForbidden();
        $this->get(route('student.materials.show', $draft, absolute: false))->assertForbidden();

        $this->app->instance(StudentMaterialAssignments::class, new AssignedVideoMaterial($student->id, $published->id));
        config(['inertia.testing.ensure_pages_exist' => false]);
        $this->get(route('student.materials.show', $published, absolute: false))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('student/Video')
                ->where('material.title', 'Video ditugaskan')
                ->where('material.embedUrl', 'https://www.youtube-nocookie.com/embed/abcdefghijk'));
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->forceFill(['role' => $role])->save();

        return $user;
    }
}

final class AssignedVideoMaterial implements StudentMaterialAssignments
{
    public function __construct(private readonly int $studentId, private readonly int $materialId) {}

    public function materialIdsFor(User $student): Collection
    {
        return $student->id === $this->studentId ? collect([$this->materialId]) : collect();
    }

    public function isAssigned(User $student, Material $material): bool
    {
        return $student->id === $this->studentId && $material->id === $this->materialId;
    }
}
