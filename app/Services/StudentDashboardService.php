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

final class StudentDashboardService
{
    public function build(User $student): array
    {
        $classroomIds = $this->enrolledClassroomIds($student);

        $classrooms = Classroom::query()
            ->whereIn('classrooms.id', clone $classroomIds)
            ->with([
                'course:id,title,subject,grade',
                'teacher:id,name',
            ])
            ->withCount('students')
            ->orderBy('classrooms.name')
            ->limit(6)
            ->get();

        $homeworkQuery = Homework::query()
            ->whereIn('classroom_id', clone $classroomIds)
            ->where('status', 'published');

        $homeworks = (clone $homeworkQuery)
            ->with('classroom:id,name,course_id')
            ->withCount([
                'submissions as submitted_by_student_count' => fn (Builder $query) => $query
                    ->where('student_id', $student->id),
            ])
            ->orderByRaw('CASE WHEN due_at IS NULL THEN 1 ELSE 0 END')
            ->orderBy('due_at')
            ->latest('created_at')
            ->limit(6)
            ->get();

        $attendanceQuery = Attendance::query()
            ->where('student_id', $student->id)
            ->whereIn('classroom_id', clone $classroomIds);

        $attendanceTotal = (clone $attendanceQuery)
            ->whereIn('status', ['present', 'late', 'absent'])
            ->count();

        $attendancePresent = (clone $attendanceQuery)
            ->whereIn('status', ['present', 'late'])
            ->count();

        $attendance = (clone $attendanceQuery)
            ->with('classroom:id,name')
            ->latest('session_date')
            ->limit(6)
            ->get();

        // Never expose a result attached to another classroom. Course-wide
        // results remain visible only while the student has active course access.
        $results = ExamResult::query()
            ->where('student_id', $student->id)
            ->whereHas('exam', function (Builder $exam) use ($student, $classroomIds): void {
                $exam->where(function (Builder $scope) use ($student, $classroomIds): void {
                    $scope->whereIn('exams.classroom_id', clone $classroomIds)
                        ->orWhere(function (Builder $courseLevel) use ($student): void {
                            $courseLevel
                                ->whereNull('exams.classroom_id')
                                ->whereHas('course.enrollments', fn (Builder $enrollments) => $enrollments
                                    ->where('student_id', $student->id)
                                    ->where('status', 'active')
                                    ->where(fn (Builder $expiry) => $expiry
                                        ->whereNull('expires_at')
                                        ->orWhere('expires_at', '>', now())));
                        });
                });
            })
            ->with(['exam:id,title,classroom_id,course_id', 'exam.classroom:id,name', 'exam.course:id,title'])
            ->latest()
            ->limit(6)
            ->get();

        $resources = LearningResource::query()
            ->whereIn('classroom_id', clone $classroomIds)
            ->whereIn('visibility', ['enrolled', 'authenticated'])
            ->where('status', 'published')
            ->latest()
            ->limit(5)
            ->get(['id', 'classroom_id', 'title', 'kind', 'is_free', 'price', 'created_at']);

        $announcements = Announcement::query()
            ->where('status', 'published')
            ->whereIn('audience', ['all', 'students'])
            ->latest('published_at')
            ->limit(4)
            ->get(['id', 'title', 'body', 'published_at']);

        return [
            'classrooms' => $classrooms,
            'classroom_count' => (clone $classroomIds)->count(),
            'homeworks' => $homeworks,
            'pending_homework_count' => (clone $homeworkQuery)->whereDoesntHave(
                'submissions',
                fn (Builder $query) => $query->where('student_id', $student->id)
            )->count(),
            'attendance' => $attendance,
            'attendance_count' => $attendanceTotal,
            'attendance_rate' => $attendanceTotal > 0
                ? (int) round($attendancePresent / $attendanceTotal * 100)
                : null,
            'results' => $results,
            'resources' => $resources,
            'resource_count' => (clone $classroomIds)->exists()
                ? LearningResource::query()
                    ->whereIn('classroom_id', clone $classroomIds)
                    ->whereIn('visibility', ['enrolled', 'authenticated'])
                    ->where('status', 'published')
                    ->count()
                : 0,
            'announcements' => $announcements,
        ];
    }

    private function enrolledClassroomIds(User $student): Builder
    {
        return Classroom::query()
            ->where('classrooms.status', 'active')
            ->whereHas('students', fn (Builder $query) => $query->whereKey($student->id))
            ->where(function (Builder $access) use ($student): void {
                // Standalone classes remain available through explicit roster membership.
                // Course-linked classes also require a current course enrollment.
                $access->whereNull('classrooms.course_id')
                    ->orWhereHas('course', fn (Builder $course) => $course
                        ->where('status', 'published')
                        ->whereHas('enrollments', fn (Builder $enrollments) => $enrollments
                            ->where('student_id', $student->id)
                            ->where('status', 'active')
                            ->where(fn (Builder $expiry) => $expiry
                                ->whereNull('expires_at')
                                ->orWhere('expires_at', '>', now()))));
            })
            ->select('classrooms.id');
    }
}
