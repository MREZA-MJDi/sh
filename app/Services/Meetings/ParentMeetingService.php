<?php

namespace App\Services\Meetings;

use App\Models\Classroom;
use App\Models\ParentMeeting;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ParentMeetingService
{
    public function visibleToGuardian(User $guardian): Builder
    {
        return ParentMeeting::query()->where('guardian_id', $guardian->id);
    }

    public function createForGuardian(User $guardian, array $data): ParentMeeting
    {
        $student = $guardian->children()->whereKey($data['student_id'])
            ->where('users.is_active', true)
            ->whereHas('roles', fn (Builder $q) => $q->where('name', 'student'))
            ->firstOrFail();

        $this->assertClassroomBelongsToStudent($data['classroom_id'] ?? null, $student->id);

        return ParentMeeting::query()->create([
            ...$data,
            'guardian_id' => $guardian->id,
            'student_id' => $student->id,
            'status' => 'requested',
        ]);
    }

    public function createByAdmin(User $admin, array $data): ParentMeeting
    {
        $guardian = User::query()->whereKey($data['guardian_id'])
            ->where('is_active', true)
            ->whereHas('roles', fn (Builder $q) => $q->where('name', 'parent'))
            ->whereHas('children', fn (Builder $q) => $q->whereKey($data['student_id'])
                ->where('is_active', true)
                ->whereHas('roles', fn (Builder $roles) => $roles->where('name', 'student')))
            ->firstOrFail();

        $this->assertClassroomBelongsToStudent($data['classroom_id'] ?? null, (int) $data['student_id']);

        return ParentMeeting::query()->create([
            ...$data,
            'created_by' => $admin->id,
            'status' => filled($data['scheduled_at'] ?? null) ? 'proposed' : 'requested',
        ]);
    }

    public function updateByAdmin(ParentMeeting $meeting, array $data): ParentMeeting
    {
        return DB::transaction(function () use ($meeting, $data): ParentMeeting {
            $meeting = ParentMeeting::query()->lockForUpdate()->findOrFail($meeting->id);
            $meeting->fill($data);
            $scheduleChanged = $meeting->isDirty(['scheduled_at', 'ends_at']);
            if ($scheduleChanged && ! in_array(($data['status'] ?? $meeting->status), ['completed', 'cancelled'], true)) {
                $meeting->status = 'proposed';
                $meeting->guardian_responded_at = null;
            }
            if (($data['status'] ?? null) === 'cancelled') {
                $meeting->scheduled_at = null;
                $meeting->ends_at = null;
            }
            $meeting->save();

            return $meeting->refresh();
        });
    }

    private function assertClassroomBelongsToStudent(mixed $classroomId, int $studentId): void
    {
        if (blank($classroomId)) {
            return;
        }

        $belongs = Classroom::query()->whereKey($classroomId)
            ->where('status', 'active')
            ->whereHas('students', fn (Builder $query) => $query->whereKey($studentId))
            ->exists();

        if (! $belongs) {
            throw ValidationException::withMessages([
                'classroom_id' => 'کلاس انتخاب‌شده متعلق به این دانش‌آموز نیست.',
            ]);
        }
    }

    public function respondAsGuardian(User $guardian, ParentMeeting $meeting, string $response): ParentMeeting
    {
        return DB::transaction(function () use ($guardian, $meeting, $response): ParentMeeting {
            $meeting = ParentMeeting::query()->lockForUpdate()->findOrFail($meeting->id);
            abort_unless((int) $meeting->guardian_id === (int) $guardian->id, 404);
            abort_unless($meeting->status === 'proposed', 422, 'فقط زمان پیشنهادی قابل پاسخ‌گویی است.');

            $meeting->forceFill([
                'status' => $response,
                'guardian_responded_at' => now(),
            ])->save();

            return $meeting->refresh();
        });
    }
}