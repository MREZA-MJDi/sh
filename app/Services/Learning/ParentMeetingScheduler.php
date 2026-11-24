<?php

namespace App\Services\Learning;

use App\Models\ParentMeeting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

final class ParentMeetingScheduler
{
    /** @return array{0: ParentMeeting, 1: bool} */
    public function transition(Builder $visibleMeetings, int $meetingId, array $input, int $actorId): array
    {
        return DB::transaction(function () use ($visibleMeetings, $meetingId, $input, $actorId): array {
            $meeting = (clone $visibleMeetings)->lockForUpdate()->findOrFail($meetingId);
            $pending = in_array($meeting->status, ['requested', 'proposed'], true);
            $scheduling = ($input['status'] ?? null) === 'scheduled'
                && ($pending || $meeting->status === 'scheduled');

            $data = Validator::make($input, [
                'status' => ['required', Rule::in(['scheduled', 'completed', 'cancelled', 'declined'])],
                'starts_at' => [Rule::excludeIf(! $scheduling), 'required', 'date', 'after:now'],
                'ends_at' => [Rule::excludeIf(! $scheduling), 'nullable', 'date', 'after:starts_at'],
            ])->validate();

            $allowed = match (true) {
                $pending => ['scheduled', 'declined', 'cancelled'],
                $meeting->status === 'scheduled' => ['scheduled', 'completed', 'cancelled'],
                default => [],
            };

            abort_unless(in_array($data['status'], $allowed, true), 422, 'وضعیت این جلسه قابل تغییر نیست.');

            $meeting->update([
                'status' => $data['status'],
                'starts_at' => $data['starts_at'] ?? $meeting->starts_at ?? $meeting->preferred_at,
                'ends_at' => $scheduling ? ($data['ends_at'] ?? null) : $meeting->ends_at,
                'responded_by' => $actorId,
                'responded_at' => now(),
            ]);

            return [$meeting, $pending];
        }, 3);
    }
}
