<?php

namespace App\Services\Learning;

use App\Models\ParentMeeting;
use App\Models\Classroom;
use App\Models\User;
use App\Notifications\ParentMeetingNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

final class ParentMeetingScheduler
{
    public function scheduleClassroom(Classroom $classroom, User $actor, array $data): ParentMeeting
    {
        abort_unless($actor->hasRole('admin'), 403);

        return DB::transaction(function () use ($classroom, $actor, $data): ParentMeeting {
            $classroom = Classroom::query()->where('status', 'active')->lockForUpdate()->findOrFail($classroom->id);
            $meeting = ParentMeeting::query()->create([
                'audience' => 'classroom',
                'classroom_id' => $classroom->id,
                'course_id' => $classroom->course_id,
                'created_by' => $actor->id,
                'title' => trim($data['title']),
                'message' => $data['message'] ?? null,
                'starts_at' => $data['starts_at'],
                'ends_at' => $data['ends_at'] ?? null,
                'status' => 'scheduled',
            ]);

            $count = 0;
            $classroom->students()->where('users.is_active', true)
                ->whereHas('roles', fn (Builder $roles) => $roles->where('name', 'student'))
                ->with(['guardians' => fn ($guardians) => $guardians->where('users.is_active', true)
                    ->whereHas('roles', fn (Builder $roles) => $roles->where('name', 'parent'))])
                ->chunkById(100, function ($students) use ($meeting, &$count): void {
                    $rows = [];
                    foreach ($students as $student) {
                        foreach ($student->guardians as $guardian) {
                            $rows[] = ['parent_meeting_id' => $meeting->id, 'student_id' => $student->id, 'guardian_id' => $guardian->id];
                        }
                    }
                    if ($rows !== []) {
                        DB::table('parent_meeting_attendees')->insertOrIgnore($rows);
                        $count += count($rows);
                    }
                }, 'users.id', 'id');

            if ($count === 0) {
                throw ValidationException::withMessages(['classroom_id' => 'برای این کلاس هیچ خانواده فعالی ثبت نشده است.']);
            }

            return $meeting;
        }, 3);
    }

    public function notifyParticipants(ParentMeeting $meeting, string $guardianMessage, string $studentMessage): void
    {
        if ($meeting->audience !== 'classroom') {
            $meeting->loadMissing(['guardian', 'student']);
            $meeting->guardian?->notify(new ParentMeetingNotification($meeting, $guardianMessage));
            $meeting->student?->notify(new ParentMeetingNotification($meeting, $studentMessage));
            return;
        }

        foreach (['guardian_id' => ['parent', $guardianMessage], 'student_id' => ['student', $studentMessage]] as $column => [$role, $message]) {
            User::query()->where('is_active', true)
                ->whereHas('roles', fn (Builder $roles) => $roles->where('name', $role))
                ->whereIn('id', DB::table('parent_meeting_attendees')
                    ->where('parent_meeting_id', $meeting->id)->select($column))
                ->chunkById(100, fn ($users) => Notification::send($users, new ParentMeetingNotification($meeting, $message)));
        }
    }

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
