<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\DailyPlanMaterial;
use App\Models\StudySession;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StudySessionController extends Controller
{
    private const TIMEZONE = 'Asia/Jakarta';

    private const MAX_HEARTBEAT_SECONDS = 45;

    public function start(Request $request): JsonResponse
    {
        $data = $request->validate(['assignment_id' => ['required', 'integer', 'min:1']]);
        $student = $request->user();
        $assignment = $this->assignedSlot($student, (int) $data['assignment_id']);
        abort_unless($assignment->plan->plan_date->toDateString() === now(self::TIMEZONE)->toDateString(), 403);

        try {
            $session = DB::transaction(function () use ($student, $assignment): StudySession {
                User::query()->whereKey($student->getKey())->lockForUpdate()->firstOrFail();
                $active = StudySession::query()->where('active_user_id', $student->getKey())->lockForUpdate()->first();
                if ($active) {
                    if ($active->daily_plan_material_id !== $assignment->getKey()) {
                        abort(409, 'Sesi belajar lain masih aktif. Hentikan sesi itu sebelum membuka materi lain.');
                    }

                    return $active;
                }

                $now = now();

                return StudySession::query()->create([
                    'public_id' => (string) Str::uuid(),
                    'user_id' => $student->getKey(),
                    'active_user_id' => $student->getKey(),
                    'daily_plan_id' => $assignment->daily_plan_id,
                    'daily_plan_material_id' => $assignment->getKey(),
                    'material_id' => $assignment->material_id,
                    'started_at' => $now,
                    'last_seen_at' => $now,
                ]);
            });
        } catch (QueryException $exception) {
            // The unique active_user_id index is the final race guard if two start requests arrive together.
            $session = StudySession::query()->where('active_user_id', $student->getKey())->first();
            if (! $session || $session->daily_plan_material_id !== $assignment->getKey()) {
                throw $exception;
            }
        }

        return response()->json($this->sessionPayload($session));
    }

    public function heartbeat(Request $request, string $session): JsonResponse
    {
        $data = $request->validate(['sequence' => ['required', 'integer', 'min:1', 'max:4294967295']]);
        $student = $request->user();
        $result = DB::transaction(function () use ($student, $session, $data): array {
            $studySession = StudySession::query()->where('public_id', $session)
                ->where('user_id', $student->getKey())->lockForUpdate()->firstOrFail();
            abort_unless($studySession->active_user_id === $student->getKey() && $studySession->ended_at === null, 409);
            $this->assignedSlot($student, $studySession->daily_plan_material_id);

            $sequence = (int) $data['sequence'];
            if ($sequence > $studySession->last_sequence + 1) {
                throw ValidationException::withMessages(['sequence' => 'Heartbeat harus berurutan. Coba kirim ulang permintaan terakhir.']);
            }
            if ($sequence > $studySession->last_sequence) {
                $this->acceptElapsedInterval($studySession, $sequence, CarbonImmutable::instance(now()));
                $studySession->forceFill(['last_sequence' => $sequence, 'last_seen_at' => now()])->save();
            }

            return $this->sessionPayload($studySession->fresh());
        });

        return response()->json($result);
    }

    public function stop(Request $request, string $session): JsonResponse
    {
        $data = $request->validate(['sequence' => ['nullable', 'integer', 'min:1', 'max:4294967295']]);
        $student = $request->user();
        $result = DB::transaction(function () use ($student, $session, $data): array {
            $studySession = StudySession::query()->where('public_id', $session)
                ->where('user_id', $student->getKey())->lockForUpdate()->firstOrFail();

            if ($studySession->ended_at === null) {
                abort_unless($studySession->active_user_id === $student->getKey(), 409);
                $this->assignedSlot($student, $studySession->daily_plan_material_id);

                if (isset($data['sequence'])) {
                    $sequence = (int) $data['sequence'];
                    if ($sequence > $studySession->last_sequence + 1) {
                        throw ValidationException::withMessages(['sequence' => 'Heartbeat harus berurutan. Coba kirim ulang permintaan terakhir.']);
                    }
                    if ($sequence > $studySession->last_sequence) {
                        $this->acceptElapsedInterval($studySession, $sequence, CarbonImmutable::instance(now()));
                        $studySession->forceFill(['last_sequence' => $sequence]);
                    }
                }

                $studySession->forceFill([
                    'active_user_id' => null,
                    'ended_at' => now(),
                    'last_seen_at' => now(),
                ])->save();
            }

            return $this->sessionPayload($studySession->fresh());
        });

        return response()->json($result);
    }

    private function assignedSlot(User $student, int $assignmentId): DailyPlanMaterial
    {
        $assignment = DailyPlanMaterial::query()
            ->with(['plan', 'material'])
            ->whereKey($assignmentId)
            ->whereHas('plan.learningClass.students', fn ($query) => $query->whereKey($student->getKey()))
            ->firstOrFail();

        abort_unless(
            $assignment->material->isPublished()
                && $assignment->material->getKey() === $assignment->material_id
                && $assignment->material->type === $assignment->type,
            403,
        );

        return $assignment;
    }

    private function acceptElapsedInterval(StudySession $session, int $sequence, CarbonImmutable $now): void
    {
        $lastSeen = CarbonImmutable::parse($session->last_seen_at);
        $elapsed = $lastSeen->lt($now) ? min($lastSeen->diffInSeconds($now), self::MAX_HEARTBEAT_SECONDS) : 0;
        if ($elapsed < 1) {
            return;
        }

        $start = $now->subSeconds($elapsed);
        $cursor = $start;
        while ($cursor->lt($now)) {
            $localCursor = $cursor->setTimezone(self::TIMEZONE);
            $nextLocalMidnight = $localCursor->startOfDay()->addDay();
            $boundary = $nextLocalMidnight->setTimezone($cursor->getTimezone());
            $sliceEnd = $boundary->lt($now) ? $boundary : $now;
            $sliceSeconds = (int) $cursor->diffInSeconds($sliceEnd);
            if ($sliceSeconds > 0) {
                $session->intervals()->create([
                    'study_date' => $localCursor->toDateString(),
                    'sequence' => $sequence,
                    'seconds' => $sliceSeconds,
                ]);
            }
            $cursor = $sliceEnd;
        }

        $session->forceFill(['seconds' => $session->seconds + $elapsed])->save();
    }

    /** @return array{sessionId: string, seconds: int, dailySeconds: int, lastSequence: int} */
    private function sessionPayload(StudySession $session): array
    {
        $today = now(self::TIMEZONE)->toDateString();
        $dailySeconds = DB::table('study_session_intervals')
            ->join('study_sessions', 'study_sessions.id', '=', 'study_session_intervals.study_session_id')
            ->where('study_sessions.user_id', $session->user_id)
            ->where('study_sessions.daily_plan_id', $session->daily_plan_id)
            ->whereDate('study_session_intervals.study_date', $today)
            ->sum('study_session_intervals.seconds');

        return [
            'sessionId' => $session->public_id,
            'seconds' => (int) $session->seconds,
            'dailySeconds' => (int) $dailySeconds,
            'lastSequence' => (int) $session->last_sequence,
        ];
    }
}
