<?php

namespace App\Services;

use App\Models\Announcement;
use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\ExamResult;
use App\Models\Homework;
use App\Models\LearningResource;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

final class GuardianDashboardService
{
    public function build(User $guardian, ?int $selectedChildId = null): array
    {
        $children = $guardian->children()
            ->where('users.is_active', true)
            ->whereHas('roles', fn (Builder $roles) => $roles->where('name', 'student'))
            ->orderBy('users.name')
            ->get(['users.id', 'users.name', 'users.grade']);

        // Resolve the requested child only through this guardian's own relationship.
        $selectedChild = $children->firstWhere('id', $selectedChildId) ?? $children->first();
        $childId = $selectedChild?->getKey();

        $classroomIds = Classroom::query()
            ->where('classrooms.status', 'active')
            ->whereHas('students', fn (Builder $students) => $students->whereKey($childId ?? 0))
            ->where(function (Builder $access) use ($childId): void {
                $access->whereNull('classrooms.course_id')
                    ->orWhereHas('course', fn (Builder $course) => $course
                        ->where('status', 'published')
                        ->whereHas('enrollments', fn (Builder $enrollments) => $enrollments
                            ->where('student_id', $childId ?? 0)
                            ->where('status', 'active')
                            ->where(fn (Builder $expiry) => $expiry
                                ->whereNull('expires_at')
                                ->orWhere('expires_at', '>', now())));
            })
            ->select('classrooms.id');

        $classrooms = $childId
            ? Classroom::query()
                ->whereIn('classrooms.id', clone $classroomIds)
                ->with(['course:id,title,subject,grade', 'teacher:id,name'])
                ->orderBy('classrooms.name')
                ->limit(12)
                ->get()
            : collect();

        $homeworkQuery = Homework::query()
            ->where('status', 'published')
            ->whereIn('classroom_id', clone $classroomIds);

        $homeworks = $childId
            ? (clone $homeworkQuery)
                ->with('classroom:id,name')
                ->withCount([
                    'submissions as submitted_by_child_count' => fn (Builder $submissions) => $submissions
                        ->where('student_id', $childId),
                ])
                ->orderByRaw('CASE WHEN due_at IS NULL THEN 1 ELSE 0 END')
                ->orderBy('due_at')
                ->latest('created_at')
                ->limit(8)
                ->get()
            : collect();

        $attendanceQuery = Attendance::query()
            ->where('student_id', $childId ?? 0)
            ->whereIn('classroom_id', clone $classroomIds)
            ->whereIn('status', ['present', 'late', 'absent']);

        $attendanceCount = $childId ? (clone $attendanceQuery)->count() : 0;
        $attendancePresentCount = $childId
            ? (clone $attendanceQuery)->whereIn('status', ['present', 'late'])->count()
            : 0;

        $attendance = $childId
            ? (clone $attendanceQuery)
                ->with('classroom:id,name')
                ->latest('session_date')
                ->limit(8)
                ->get()
            : collect();

        $results = $childId
            ? ExamResult::query()
                ->where('student_id', $childId)
                ->whereHas('exam', fn (Builder $exam) => $exam
                    ->whereIn('exams.classroom_id', clone $classroomIds)
                    ->orWhere(function (Builder $courseLevel) use ($childId): void {
                        $courseLevel->whereNull('exams.classroom_id')
                            ->whereHas('course.enrollments', fn (Builder $enrollments) => $enrollments
                                ->where('student_id', $childId)
                                ->where('status', 'active')
                                ->where(fn (Builder $expiry) => $expiry
                                    ->whereNull('expires_at')
                                    ->orWhere('expires_at', '>', now())));
                    }))
                ->with(['exam:id,title,classroom_id,course_id', 'exam.classroom:id,name', 'exam.course:id,title'])
                ->latest()
                ->limit(8)
                ->get()
            : collect();

        $resources = $childId
            ? LearningResource::query()
                ->whereIn('classroom_id', clone $classroomIds)
                ->whereIn('visibility', ['enrolled', 'authenticated'])
                ->where('status', 'published')
                ->latest()
                ->limit(8)
                ->get(['id', 'classroom_id', 'title', 'kind', 'is_free', 'price', 'created_at'])
            : collect();

        $announcements = Announcement::query()
            ->where('status', 'published')
            ->whereIn('audience', ['all', 'parents'])
            ->latest('published_at')
            ->limit(5)
            ->get(['id', 'title', 'body', 'published_at']);

        return [
            'children' => $children,
            'selectedChild' => $selectedChild,
            'classrooms' => $classrooms,
            'classroom_count' => $childId ? (clone $classroomIds)->count() : 0,
            'homeworks' => $homeworks,
            'pending_homework_count' => $childId
                ? (clone $homeworkQuery)->whereDoesntHave(
                    'submissions',
                    fn (Builder $submissions) => $submissions->where('student_id', $childId)
                )->count()
                : 0,
            'attendance' => $attendance,
            'attendance_count' => $attendanceCount,
            'attendance_rate' => $attendanceCount > 0
                ? (int) round($attendancePresentCount / $attendanceCount * 100)
                : null,
            'results' => $results,
            'resources' => $resources,
            'resource_count' => $childId
                ? LearningResource::query()
                    ->whereIn('classroom_id', clone $classroomIds)
                    ->whereIn('visibility', ['enrolled', 'authenticated'])
                    ->where('status', 'published')
                    ->count()
                : 0,
            'announcements' => $announcements,
        ];
    }
}
