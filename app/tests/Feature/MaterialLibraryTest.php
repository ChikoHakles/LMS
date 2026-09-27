<?php

namespace Tests\Feature;

use App\Contracts\StudentMaterialAssignments;
use App\Models\Material;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class MaterialLibraryTest extends TestCase
{
    use RefreshDatabase;

    public function test_tutor_can_save_a_draft_article_and_the_tutor_is_recorded_as_owner(): void
    {
        $tutor = $this->userWithRole(User::ROLE_TUTOR);
        $this->actingAs($tutor);

        $this->post(route('tutor.materials.store', absolute: false), [
            'title' => 'Belajar menjaga lingkungan',
            'summary' => 'Ringkasan artikel.',
            'blocks' => [
                ['type' => 'heading', 'text' => 'Mulai dari rumah'],
                ['type' => 'paragraph', 'text' => 'Gunakan kembali barang yang masih layak.'],
            ],
        ])->assertRedirect();

        $material = Material::query()->where('title', 'Belajar menjaga lingkungan')->firstOrFail();
        $this->assertSame($tutor->id, $material->owner_id);
        $this->assertSame(Material::STATUS_DRAFT, $material->status);
        $this->assertNull($material->published_at);
        $this->assertSame([
            ['type' => 'heading', 'text' => 'Mulai dari rumah', 'level' => 2],
            ['type' => 'paragraph', 'text' => 'Gunakan kembali barang yang masih layak.'],
        ], $material->blocks);
    }

    public function test_a_different_tutor_cannot_edit_or_publish_another_tutors_material(): void
    {
        $owner = $this->userWithRole(User::ROLE_TUTOR);
        $otherTutor = $this->userWithRole(User::ROLE_TUTOR);
        $material = $owner->materials()->create($this->articleAttributes());
        $this->actingAs($otherTutor);

        $payload = ['title' => 'Changed title', 'summary' => null, 'blocks' => [['type' => 'paragraph', 'text' => 'Changed content.']]];
        $this->put(route('tutor.materials.update', $material, absolute: false), $payload)->assertForbidden();
        $this->get(route('tutor.materials.show', $material, absolute: false))->assertForbidden();
        $this->patch(route('tutor.materials.publish', $material, absolute: false))->assertForbidden();
        $this->assertSame('Sample article', $material->fresh()->title);
        $this->assertSame(Material::STATUS_DRAFT, $material->fresh()->status);
    }

    public function test_tutor_library_filters_by_type_and_only_lists_owned_materials(): void
    {
        $tutor = $this->userWithRole(User::ROLE_TUTOR);
        $anotherTutor = $this->userWithRole(User::ROLE_TUTOR);
        $tutor->materials()->create($this->articleAttributes());
        $tutor->materials()->create($this->articleAttributes(type: Material::TYPE_VIDEO, title: 'Video lesson'));
        $anotherTutor->materials()->create($this->articleAttributes(title: 'Other tutor article'));
        $this->actingAs($tutor);

        $this->get(route('tutor.materials.index', ['type' => Material::TYPE_VIDEO], absolute: false))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('tutor/materials/Index')
                ->where('materials.total', 1)
                ->where('materials.data.0.title', 'Video lesson')
                ->where('filters.type', Material::TYPE_VIDEO));
    }

    public function test_students_cannot_read_drafts_or_published_materials_without_an_assignment(): void
    {
        $owner = $this->userWithRole(User::ROLE_TUTOR);
        $student = $this->userWithRole(User::ROLE_STUDENT);
        $draft = $owner->materials()->create($this->articleAttributes());
        $published = $owner->materials()->create($this->articleAttributes(title: 'Published article', status: Material::STATUS_PUBLISHED));
        $this->actingAs($student);

        $this->get(route('student.materials.show', $draft, absolute: false))->assertForbidden();
        $this->get(route('student.materials.show', $published, absolute: false))->assertForbidden();
        $this->get(route('student.materials.index', absolute: false))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('student/Materials/Index')->where('materials.total', 0));
    }

    public function test_student_can_read_published_material_only_when_assignment_boundary_confirms_it(): void
    {
        $owner = $this->userWithRole(User::ROLE_TUTOR);
        $student = $this->userWithRole(User::ROLE_STUDENT);
        $assigned = $owner->materials()->create($this->articleAttributes(title: 'Assigned article', status: Material::STATUS_PUBLISHED));
        $draft = $owner->materials()->create($this->articleAttributes(title: 'Assigned draft'));
        $assignments = new TestStudentMaterialAssignments([$student->id => [$assigned->id, $draft->id]]);
        $this->app->instance(StudentMaterialAssignments::class, $assignments);
        $this->actingAs($student);

        $this->get(route('student.materials.show', $assigned, absolute: false))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('materials/Show')->where('material.title', 'Assigned article'));
        $this->get(route('student.materials.show', $draft, absolute: false))->assertForbidden();
        $this->get(route('student.materials.index', absolute: false))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('student/Materials/Index')
                ->where('materials.total', 1)
                ->where('materials.data.0.title', 'Assigned article'));
    }

    public function test_invalid_block_shapes_and_unsafe_link_schemes_are_rejected_without_php_errors(): void
    {
        $tutor = $this->userWithRole(User::ROLE_TUTOR);
        $this->actingAs($tutor);

        $base = ['title' => 'Invalid input', 'summary' => null];
        $this->post(route('tutor.materials.store', absolute: false), $base + [
            'blocks' => [['type' => ['unexpected'], 'text' => 'No crash']],
        ])->assertSessionHasErrors('blocks.0.type');

        $this->post(route('tutor.materials.store', absolute: false), $base + [
            'blocks' => [['type' => 'link', 'text' => 'Unsafe', 'url' => 'javascript:alert(1)']],
        ])->assertSessionHasErrors('blocks.0.url');

        $this->post(route('tutor.materials.store', absolute: false), $base + [
            'blocks' => [['type' => 'paragraph', 'text' => 'Hello', 'html' => '<script>alert(1)</script>']],
        ])->assertSessionHasErrors('blocks.0');
        $this->assertSame(0, Material::query()->count());
    }

    public function test_article_publishing_requires_owner_and_content_and_plain_text_is_round_tripped(): void
    {
        $owner = $this->userWithRole(User::ROLE_TUTOR);
        $material = $owner->materials()->create($this->articleAttributes(blocks: [
            ['type' => 'paragraph', 'text' => '<script>alert("x")</script>'],
        ]));
        $this->actingAs($owner);

        $this->patch(route('tutor.materials.publish', $material, absolute: false))->assertRedirect();
        $material->refresh();
        $this->assertSame(Material::STATUS_PUBLISHED, $material->status);
        $this->assertNotNull($material->published_at);
        $this->assertSame('<script>alert("x")</script>', $material->blocks[0]['text']);

        $empty = $owner->materials()->create($this->articleAttributes(title: 'Empty', blocks: []));
        $this->patch(route('tutor.materials.publish', $empty, absolute: false))->assertSessionHasErrors('blocks');
        $this->assertSame(Material::STATUS_DRAFT, $empty->fresh()->status);
    }

    public function test_publish_policy_is_owner_only_for_each_material_type(): void
    {
        $owner = $this->userWithRole(User::ROLE_TUTOR);
        $otherTutor = $this->userWithRole(User::ROLE_TUTOR);
        $video = $owner->materials()->create($this->articleAttributes(type: Material::TYPE_VIDEO, title: 'Video lesson'));

        $this->assertTrue(Gate::forUser($owner)->allows('publish', $video));
        $this->assertFalse(Gate::forUser($otherTutor)->allows('publish', $video));
    }

    private function articleAttributes(
        string $type = Material::TYPE_ARTICLE,
        string $title = 'Sample article',
        string $status = Material::STATUS_DRAFT,
        array $blocks = [['type' => 'paragraph', 'text' => 'A readable article body.']],
    ): array {
        return [
            'type' => $type,
            'title' => $title,
            'summary' => 'Sample summary',
            'status' => $status,
            'published_at' => $status === Material::STATUS_PUBLISHED ? now() : null,
            'blocks' => $blocks,
        ];
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->forceFill(['role' => $role])->save();

        return $user;
    }
}

/** Fake adapter for proving the policy/list contract before RUANG-40 wires daily plans. */
final class TestStudentMaterialAssignments implements StudentMaterialAssignments
{
    /** @param array<int, list<int>> $assignments */
    public function __construct(private readonly array $assignments) {}

    public function materialIdsFor(User $student): Collection
    {
        return collect($this->assignments[$student->id] ?? []);
    }

    public function isAssigned(User $student, Material $material): bool
    {
        return in_array($material->id, $this->assignments[$student->id] ?? [], true);
    }
}
