<?php

namespace Tests\Feature;

use App\Models\DailyPlan;
use App\Models\LearningClass;
use App\Models\Material;
use App\Models\User;
use App\Services\DailyPlanStudentMaterialAssignments;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DailyPlanTest extends TestCase
{
    use RefreshDatabase;

    public function test_tutor_can_create_a_class_and_enroll_only_active_student_accounts(): void
    {
        $tutor = $this->userWithRole(User::ROLE_TUTOR);
        $student = $this->userWithRole(User::ROLE_STUDENT);
        $inactive = $this->userWithRole(User::ROLE_STUDENT, User::STATUS_INACTIVE);

        $this->actingAs($tutor)
            ->post(route('tutor.classes.store', absolute: false), ['name' => 'Kelas A'])
            ->assertRedirect();
        $class = LearningClass::query()->where('tutor_id', $tutor->id)->firstOrFail();
        $this->put(route('tutor.classes.students.update', $class, absolute: false), ['student_ids' => [$student->id]])
            ->assertRedirect();

        $this->assertTrue($class->students()->whereKey($student->id)->exists());
        $this->put(route('tutor.classes.students.update', $class, absolute: false), ['student_ids' => [$inactive->id]])
            ->assertSessionHasErrors('student_ids');
        $this->assertTrue($class->fresh()->students()->whereKey($student->id)->exists());
    }

    public function test_tutor_can_save_one_plan_per_class_date_with_matching_ordered_published_slots(): void
    {
        $tutor = $this->userWithRole(User::ROLE_TUTOR);
        $class = $this->classFor($tutor);
        $article = $this->published($tutor, Material::TYPE_ARTICLE, 'Artikel');
        $video = $this->published($tutor, Material::TYPE_VIDEO, 'Video');
        $quiz = $this->published($tutor, Material::TYPE_QUIZ, 'Kuis');
        $this->actingAs($tutor);

        $payload = [
            'class_id' => $class->id,
            'date' => '2026-10-03',
            'target_minutes' => 55,
            'materials' => [
                ['type' => 'quiz', 'material_id' => $quiz->id],
                ['type' => 'article', 'material_id' => $article->id],
                ['type' => 'video', 'material_id' => $video->id],
            ],
        ];
        $this->post(route('tutor.daily-plans.store', absolute: false), $payload)->assertRedirect();
        $this->post(route('tutor.daily-plans.store', absolute: false), array_merge($payload, ['target_minutes' => 80]))->assertRedirect();

        $this->assertSame(1, DailyPlan::query()->where('class_id', $class->id)->whereDate('plan_date', '2026-10-03')->count());
        $plan = DailyPlan::query()->where('class_id', $class->id)->firstOrFail();
        $this->assertSame(80, $plan->target_minutes);
        $this->assertSame(['article', 'video', 'quiz'], $plan->materials()->pluck('type')->all());
        $this->assertSame([$article->id, $video->id, $quiz->id], $plan->materials()->pluck('material_id')->all());
        $this->assertSame([1, 2, 3], $plan->materials()->pluck('position')->all());
    }

    public function test_tutor_cannot_assign_a_wrong_type_draft_or_another_tutors_material(): void
    {
        $tutor = $this->userWithRole(User::ROLE_TUTOR);
        $otherTutor = $this->userWithRole(User::ROLE_TUTOR);
        $class = $this->classFor($tutor);
        $video = $this->published($tutor, Material::TYPE_VIDEO, 'Video');
        $draft = $otherTutor->materials()->create($this->materialAttributes(Material::TYPE_ARTICLE, 'Draft'));
        $this->actingAs($tutor);
        $base = ['class_id' => $class->id, 'date' => '2026-10-04', 'target_minutes' => 45];

        $this->post(route('tutor.daily-plans.store', absolute: false), $base + ['materials' => [
            ['type' => Material::TYPE_ARTICLE, 'material_id' => $video->id],
        ]])->assertSessionHasErrors('materials');
        $this->post(route('tutor.daily-plans.store', absolute: false), $base + ['materials' => [
            ['type' => Material::TYPE_ARTICLE, 'material_id' => $draft->id],
        ]])->assertSessionHasErrors('materials');
        $this->assertDatabaseCount('daily_plans', 0);
    }

    public function test_other_tutors_cannot_manage_classes_or_save_plans_for_them(): void
    {
        $owner = $this->userWithRole(User::ROLE_TUTOR);
        $otherTutor = $this->userWithRole(User::ROLE_TUTOR);
        $student = $this->userWithRole(User::ROLE_STUDENT);
        $class = $this->classFor($owner);
        $this->actingAs($otherTutor);

        $this->put(route('tutor.classes.students.update', $class, absolute: false), ['student_ids' => [$student->id]])
            ->assertNotFound();
        $this->post(route('tutor.daily-plans.store', absolute: false), [
            'class_id' => $class->id,
            'date' => '2026-10-05',
            'target_minutes' => 45,
            'materials' => [],
        ])->assertNotFound();
        $this->assertDatabaseCount('daily_plans', 0);
    }

    public function test_student_material_access_is_limited_to_published_materials_assigned_to_their_class(): void
    {
        $tutor = $this->userWithRole(User::ROLE_TUTOR);
        $student = $this->userWithRole(User::ROLE_STUDENT);
        $otherStudent = $this->userWithRole(User::ROLE_STUDENT);
        $class = $this->classFor($tutor, $student);
        $assigned = $this->published($tutor, Material::TYPE_ARTICLE, 'Assigned article');
        $unassigned = $this->published($tutor, Material::TYPE_ARTICLE, 'Unassigned article');
        $draft = $tutor->materials()->create($this->materialAttributes(Material::TYPE_ARTICLE, 'Draft'));
        $plan = $class->dailyPlans()->create(['plan_date' => '2026-10-06', 'target_minutes' => 45, 'tutor_id' => $tutor->id]);
        $plan->materials()->create(['material_id' => $assigned->id, 'tutor_id' => $tutor->id, 'type' => Material::TYPE_ARTICLE, 'position' => 1]);
        $assignments = app(DailyPlanStudentMaterialAssignments::class);

        $this->assertTrue($assignments->isAssigned($student, $assigned));
        $this->assertFalse($assignments->isAssigned($student, $unassigned));
        $this->assertFalse($assignments->isAssigned($otherStudent, $assigned));
        $this->assertFalse($assignments->isAssigned($student, $draft));
        $this->actingAs($student)->get(route('student.materials.show', $assigned, absolute: false))->assertOk();
        $this->get(route('student.materials.show', $unassigned, absolute: false))->assertForbidden();
        $this->get(route('student.materials.show', $draft, absolute: false))->assertForbidden();
    }

    private function classFor(User $tutor, ?User $student = null): LearningClass
    {
        $class = $tutor->tutorClasses()->create(['name' => 'Kelas '.$tutor->id.' '.uniqid()]);
        if ($student) {
            $class->students()->attach($student->id);
        }

        return $class;
    }

    private function published(User $owner, string $type, string $title): Material
    {
        return $owner->materials()->create($this->materialAttributes($type, $title, Material::STATUS_PUBLISHED));
    }

    private function materialAttributes(string $type, string $title, string $status = Material::STATUS_DRAFT): array
    {
        return [
            'type' => $type,
            'title' => $title,
            'summary' => null,
            'status' => $status,
            'published_at' => $status === Material::STATUS_PUBLISHED ? now() : null,
        ];
    }

    private function userWithRole(string $role, string $status = User::STATUS_ACTIVE): User
    {
        $user = User::factory()->create();
        $user->forceFill(['role' => $role, 'status' => $status])->save();

        return $user;
    }
}
