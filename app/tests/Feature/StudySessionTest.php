<?php

namespace Tests\Feature;

use App\Models\DailyPlan;
use App\Models\DailyPlanMaterial;
use App\Models\LearningClass;
use App\Models\Material;
use App\Models\StudySession;
use App\Models\StudySessionInterval;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class StudySessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_server_ignores_client_time_caps_server_elapsed_and_deduplicates_retries(): void
    {
        $this->travelTo(Carbon::parse('2026-09-28 09:00:00', 'Asia/Jakarta'));
        [$student, $slot] = $this->assignedSlot(Material::TYPE_ARTICLE);
        $this->actingAs($student);

        $start = $this->postJson(route('student.study-sessions.start', absolute: false), [
            'assignment_id' => $slot->id,
            'seconds' => 999999,
            'total_seconds' => 999999,
        ])->assertOk()->assertJsonPath('seconds', 0);
        $sessionId = $start->json('sessionId');

        $this->travel(90)->seconds();
        $this->postJson(route('student.study-sessions.heartbeat', $sessionId, absolute: false), ['sequence' => 4294967295])
            ->assertUnprocessable()->assertJsonValidationErrors('sequence');
        $payload = ['sequence' => 1, 'seconds' => 999999, 'total_seconds' => 999999];
        $heartbeat = $this->postJson(route('student.study-sessions.heartbeat', $sessionId, absolute: false), $payload);
        $heartbeat->assertOk()->assertJsonPath('seconds', 45)->assertJsonPath('dailySeconds', 45);
        $this->postJson(route('student.study-sessions.heartbeat', $sessionId, absolute: false), $payload)
            ->assertOk()->assertJsonPath('seconds', 45);

        $this->assertSame(45, StudySession::query()->firstOrFail()->seconds);
        $this->assertSame(1, StudySessionInterval::query()->count());

        $this->travel(60)->seconds();
        $this->postJson(route('student.study-sessions.stop', $sessionId, absolute: false), ['sequence' => 2])
            ->assertOk()->assertJsonPath('seconds', 90);
        $this->postJson(route('student.study-sessions.stop', $sessionId, absolute: false), ['sequence' => 2])
            ->assertOk()->assertJsonPath('seconds', 90);
        $this->assertNull(StudySession::query()->firstOrFail()->active_user_id);
        $this->assertSame(2, StudySessionInterval::query()->count());
    }

    public function test_start_reuses_same_assignment_session_and_rejects_overlapping_material(): void
    {
        [$student, $slot, $plan, $tutor] = $this->assignedSlot(Material::TYPE_ARTICLE);
        $other = $this->publishedMaterial($tutor, Material::TYPE_VIDEO);
        $otherSlot = $plan->materials()->create([
            'material_id' => $other->id,
            'tutor_id' => $tutor->id,
            'type' => Material::TYPE_VIDEO,
            'position' => 2,
        ]);
        $this->actingAs($student);

        $first = $this->postJson(route('student.study-sessions.start', absolute: false), ['assignment_id' => $slot->id])
            ->assertOk();
        $this->postJson(route('student.study-sessions.start', absolute: false), ['assignment_id' => $slot->id])
            ->assertOk()->assertJsonPath('sessionId', $first->json('sessionId'));
        $this->postJson(route('student.study-sessions.start', absolute: false), ['assignment_id' => $otherSlot->id])
            ->assertConflict();

        $this->assertSame(1, StudySession::query()->count());
        $this->assertSame(1, StudySession::query()->where('active_user_id', $student->id)->count());
    }

    public function test_start_recovers_a_session_after_a_dropped_leave_signal_without_counting_the_idle_gap(): void
    {
        $this->travelTo(Carbon::parse('2026-09-28 09:00:00', 'Asia/Jakarta'));
        [$student, $slot] = $this->assignedSlot(Material::TYPE_ARTICLE);
        $this->actingAs($student);
        $firstId = $this->postJson(route('student.study-sessions.start', absolute: false), ['assignment_id' => $slot->id])
            ->assertOk()->json('sessionId');

        $this->travel(60)->seconds();
        $second = $this->postJson(route('student.study-sessions.start', absolute: false), ['assignment_id' => $slot->id])
            ->assertOk()->assertJsonPath('seconds', 0);

        $this->assertNotSame($firstId, $second->json('sessionId'));
        $old = StudySession::query()->where('public_id', $firstId)->firstOrFail();
        $this->assertNull($old->active_user_id);
        $this->assertSame('2026-09-28 09:00:00', $old->ended_at->format('Y-m-d H:i:s'));
        $this->assertSame(0, $old->seconds);
    }

    public function test_only_student_members_can_start_for_a_published_matching_dated_slot(): void
    {
        [$student, $slot, , $tutor, $class] = $this->assignedSlot(Material::TYPE_ARTICLE);
        $outsider = $this->userWithRole(User::ROLE_STUDENT);
        $admin = $this->userWithRole(User::ROLE_ADMIN);
        $draft = $tutor->materials()->create([
            'type' => Material::TYPE_ARTICLE,
            'title' => 'Draft',
            'status' => Material::STATUS_DRAFT,
        ]);
        $draftClass = $tutor->tutorClasses()->create(['name' => 'Draft class']);
        $draftClass->students()->attach($student->id);
        $draftPlan = $draftClass->dailyPlans()->create([
            'tutor_id' => $tutor->id,
            'plan_date' => now('Asia/Jakarta')->toDateString(),
            'target_minutes' => 45,
        ]);
        $draftSlot = $draftPlan->materials()->create([
            'material_id' => $draft->id,
            'tutor_id' => $tutor->id,
            'type' => Material::TYPE_ARTICLE,
            'position' => 2,
        ]);
        $foreignClass = $tutor->tutorClasses()->create(['name' => 'Not this class']);
        $foreignPlan = $foreignClass->dailyPlans()->create([
            'tutor_id' => $tutor->id,
            'plan_date' => now('Asia/Jakarta')->toDateString(),
            'target_minutes' => 45,
        ]);
        $foreignSlot = $foreignPlan->materials()->create([
            'material_id' => $slot->material_id,
            'tutor_id' => $tutor->id,
            'type' => Material::TYPE_ARTICLE,
            'position' => 1,
        ]);

        $this->actingAs($student)->postJson(route('student.study-sessions.start', absolute: false), ['assignment_id' => $slot->id])
            ->assertOk();
        $this->postJson(route('student.study-sessions.start', absolute: false), ['assignment_id' => $draftSlot->id])
            ->assertForbidden();
        $this->actingAs($outsider)->postJson(route('student.study-sessions.start', absolute: false), ['assignment_id' => $slot->id])
            ->assertNotFound();
        $this->actingAs($admin)->postJson(route('student.study-sessions.start', absolute: false), ['assignment_id' => $slot->id])
            ->assertForbidden();
        $this->actingAs($tutor)->postJson(route('student.study-sessions.start', absolute: false), ['assignment_id' => $slot->id])
            ->assertForbidden();
        $this->actingAs($student)->postJson(route('student.study-sessions.start', absolute: false), ['assignment_id' => $foreignSlot->id])
            ->assertNotFound();

        $this->assertSame(1, StudySession::query()->count());
    }

    public function test_interval_that_crosses_jakarta_midnight_is_split_into_server_date_buckets(): void
    {
        $this->travelTo(Carbon::parse('2026-09-27 16:59:30', 'UTC'));
        [$student, $slot] = $this->assignedSlot(Material::TYPE_ARTICLE, '2026-09-27');
        $this->actingAs($student);
        $sessionId = $this->postJson(route('student.study-sessions.start', absolute: false), ['assignment_id' => $slot->id])
            ->assertOk()->json('sessionId');

        $this->travel(60)->seconds();
        $this->postJson(route('student.study-sessions.heartbeat', $sessionId, absolute: false), ['sequence' => 1])
            ->assertOk()->assertJsonPath('seconds', 45)->assertJsonPath('dailySeconds', 30);

        $buckets = StudySessionInterval::query()->orderBy('study_date')->get(['study_date', 'seconds']);
        $this->assertCount(2, $buckets);
        $this->assertSame('2026-09-27', $buckets[0]->study_date->toDateString());
        $this->assertSame(15, $buckets[0]->seconds);
        $this->assertSame('2026-09-28', $buckets[1]->study_date->toDateString());
        $this->assertSame(30, $buckets[1]->seconds);
    }

    public function test_heartbeat_is_private_to_own_session_and_finished_sessions_cannot_resume(): void
    {
        [$student, $slot] = $this->assignedSlot(Material::TYPE_QUIZ);
        $other = $this->userWithRole(User::ROLE_STUDENT);
        $this->actingAs($student);
        $sessionId = $this->postJson(route('student.study-sessions.start', absolute: false), ['assignment_id' => $slot->id])
            ->assertOk()->json('sessionId');

        $this->actingAs($other)->postJson(route('student.study-sessions.heartbeat', $sessionId, absolute: false), ['sequence' => 1])
            ->assertNotFound();
        $this->actingAs($student)->postJson(route('student.study-sessions.stop', $sessionId, absolute: false))->assertOk();
        $this->postJson(route('student.study-sessions.heartbeat', $sessionId, absolute: false), ['sequence' => 1])->assertConflict();
        $this->assertDatabaseCount('study_session_intervals', 0);
    }

    public function test_current_assigned_article_video_and_quiz_pages_receive_timer_context_and_rhythm_uses_server_intervals(): void
    {
        $date = now('Asia/Jakarta')->toDateString();
        [$student, $articleSlot, $plan, $tutor, $class] = $this->assignedSlot(Material::TYPE_ARTICLE, $date);
        $video = $this->publishedMaterial($tutor, Material::TYPE_VIDEO);
        $videoSlot = $plan->materials()->create([
            'material_id' => $video->id,
            'tutor_id' => $tutor->id,
            'type' => Material::TYPE_VIDEO,
            'position' => 2,
        ]);
        $quiz = $this->publishedMaterial($tutor, Material::TYPE_QUIZ);
        $quizSlot = $plan->materials()->create([
            'material_id' => $quiz->id,
            'tutor_id' => $tutor->id,
            'type' => Material::TYPE_QUIZ,
            'position' => 3,
        ]);
        $previousPlan = $class->dailyPlans()->create([
            'tutor_id' => $tutor->id,
            'plan_date' => now('Asia/Jakarta')->subDay()->toDateString(),
            'target_minutes' => 45,
        ]);
        $previousArticleSlot = $previousPlan->materials()->create([
            'material_id' => $articleSlot->material_id,
            'tutor_id' => $tutor->id,
            'type' => Material::TYPE_ARTICLE,
            'position' => 1,
        ]);

        $this->actingAs($student);
        $this->get(route('student.materials.show', ['material' => $articleSlot->material_id, 'assignment' => $articleSlot->id], absolute: false))
            ->assertOk()->assertInertia(fn ($page) => $page
            ->component('materials/Show')
            ->where('timerContext.assignmentId', $articleSlot->id));
        $this->get(route('student.materials.show', ['material' => $video->id, 'assignment' => $videoSlot->id], absolute: false))
            ->assertOk()->assertInertia(fn ($page) => $page
            ->component('student/Video')
            ->where('timerContext.assignmentId', $videoSlot->id));
        $this->get(route('student.quizzes.show', ['material' => $quiz->id, 'assignment' => $quizSlot->id], absolute: false))
            ->assertOk()->assertInertia(fn ($page) => $page
            ->component('student/Quiz')
            ->where('timerContext.assignmentId', $quizSlot->id));
        $this->get(route('student.materials.show', $articleSlot->material_id, absolute: false))
            ->assertOk()->assertInertia(fn ($page) => $page->where('timerContext', null));
        $this->get(route('student.materials.show', ['material' => $articleSlot->material_id, 'assignment' => $previousArticleSlot->id], absolute: false))
            ->assertOk()->assertInertia(fn ($page) => $page->where('timerContext', null));
        $this->postJson(route('student.study-sessions.start', absolute: false), ['assignment_id' => $previousArticleSlot->id])
            ->assertForbidden();

        $session = StudySession::query()->create([
            'public_id' => (string) Str::uuid(),
            'user_id' => $student->id,
            'daily_plan_id' => $plan->id,
            'daily_plan_material_id' => $articleSlot->id,
            'material_id' => $articleSlot->material_id,
            'started_at' => now(),
            'last_seen_at' => now(),
            'ended_at' => now(),
            'seconds' => 125,
        ]);
        $session->intervals()->create(['study_date' => $date, 'sequence' => 1, 'seconds' => 125]);

        $this->get(route('student.daily-rhythm', ['date' => $date, 'class_id' => $class->id], absolute: false))
            ->assertOk()->assertInertia(fn ($page) => $page->where('plan.activeMinutes', 2));

        $this->actingAs($tutor)->get(route('tutor.materials.show', $articleSlot->material_id, absolute: false))
            ->assertOk()->assertInertia(fn ($page) => $page->component('materials/Show')->where('canEdit', true));
    }

    /** @return array{User, DailyPlanMaterial, DailyPlan, User, LearningClass} */
    private function assignedSlot(string $type, ?string $date = null): array
    {
        $tutor = $this->userWithRole(User::ROLE_TUTOR);
        $student = $this->userWithRole(User::ROLE_STUDENT);
        $class = $tutor->tutorClasses()->create(['name' => 'Kelas '.$tutor->id.' '.uniqid()]);
        $class->students()->attach($student->id);
        $plan = $class->dailyPlans()->create([
            'tutor_id' => $tutor->id,
            'plan_date' => $date ?? now('Asia/Jakarta')->toDateString(),
            'target_minutes' => 45,
        ]);
        $material = $this->publishedMaterial($tutor, $type);
        $slot = $plan->materials()->create([
            'material_id' => $material->id,
            'tutor_id' => $tutor->id,
            'type' => $type,
            'position' => 1,
        ]);

        return [$student, $slot, $plan, $tutor, $class];
    }

    private function publishedMaterial(User $tutor, string $type): Material
    {
        return $tutor->materials()->create([
            'type' => $type,
            'title' => 'Materi '.$type,
            'status' => Material::STATUS_PUBLISHED,
            'published_at' => now(),
            'youtube_video_id' => $type === Material::TYPE_VIDEO ? 'dQw4w9WgXcQ' : null,
        ]);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->forceFill(['role' => $role])->save();

        return $user;
    }
}
