<?php

namespace App\Support;

use App\Models\DailyPlanMaterial;

class StudyTimerContext
{
    /** @return array{assignmentId: int, startUrl: string, heartbeatUrl: string, stopUrl: string, csrfToken: string}|null */
    public static function forAssignment(?DailyPlanMaterial $assignment): ?array
    {
        if (! $assignment || $assignment->plan->plan_date->toDateString() !== now('Asia/Jakarta')->toDateString()) {
            return null;
        }

        return [
            'assignmentId' => (int) $assignment->getKey(),
            'startUrl' => route('student.study-sessions.start', absolute: false),
            'heartbeatUrl' => route('student.study-sessions.heartbeat', ['session' => '__SESSION_ID__'], absolute: false),
            'stopUrl' => route('student.study-sessions.stop', ['session' => '__SESSION_ID__'], absolute: false),
            'csrfToken' => csrf_token(),
        ];
    }
}
