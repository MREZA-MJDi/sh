<?php

namespace App\Services;

use App\Models\Announcement;
use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\Exam;
use App\Models\Homework;
use App\Models\HomeworkSubmission;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

final class TeacherDashboardService
{
    public function build(User $teacher): array
    {
        $classroomIds = $this->assignedClassroomIds($teacher);

        $classrooms = Classroom::query()
            ->whereIn('classrooms.id', clone $classroomIds)
            ->with('course:id,title,subject,grade')
            ->withCount('students')
            ->orderBy('name')
            ->limit(8)
            ->get();

        $studentCount = DB::table('classroom_student')
            ->whereIn('classroom_id', clone $classroomIds)
            ->distinct()
            ->count('student_id');

        $students = User::query()
            ->where('is_active', true)
            ->whereHas('roles', fn (Builder $query) => $query->where('name', 'student'))
            ->whereHas('enrolledClasses', fn (Builder $query) => $query
                ->whereIn('classrooms.id', clone $classroomIds))
            ->orderBy('name')
            ->limit(8)
            ->get(['id', 'name', 'grade']);

        $pendingSubmissionQuery = HomeworkSubmission::query()
            ->where('status', 'submitted')
            ->whereHas('homework', fn (Builder $query) => $query
                ->whereIn('classroom_id', clone $classroomIds));

        $submissions = (clone $pendingSubmissionQuery)
            ->with(['student:id,name,grade', 'homework:id,title,classroom_id', 'homework.classroom:id,name'])
            ->latest('submitted_at')
            ->limit(6)
            ->get();

        $homeworks = Homework::query()
            ->whereIn('classroom_id', clone $classroomIds)
            ->where('status', 'published')
            ->with('classroom:id,name')
            ->withCount('submissions')
            ->orderByRaw('CASE WHEN due_at IS NULL THEN 1 ELSE 0 END')
            ->orderBy('due_at')
            ->limit(6)
            ->get();

        $attendanceByStatus = Attendance::query()
            ->whereIn('classroom_id', clone $classroomIds)
            ->whereDate('session_date', today())
            ->whereIn('status', ['present', 'late', 'absent'])
            ->select('status')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $upcomingExams = Exam::query()
            ->whereIn('classroom_id', clone $classroomIds)
            ->where('status', 'published')
            ->where(function (Builder $query): void {
                $query->whereNull('starts_at')->orWhere('starts_at', '>=', now());
            })
            ->with('classroom:id,name')
            ->orderBy('starts_at')
            ->limit(5)
            ->get(['id', 'classroom_id', 'title', 'starts_at', 'status']);

        $courses = Course::query()
            ->where(function (Builder $query) use ($teacher, $classroomIds): void {
                $query->where('teacher_id', $teacher->id)
                    ->orWhereHas('classrooms', fn (Builder $classroomsQuery) => $classroomsQuery
                        ->whereIn('classrooms.id', clone $classroomIds));
            })
            ->withCount('classrooms')
            ->orderBy('title')
            ->limit(6)
            ->get(['id', 'title', 'subject', 'grade', 'status']);

        $announcements = Announcement::query()
            ->where('status', 'published')
            ->whereIn('audience', ['all', 'teachers'])
            ->latest('published_at')
            ->limit(4)
            ->get(['id', 'title', 'body', 'published_at']);

        return [
            'classrooms' => $classrooms,
            'classroom_count' => (clone $classroomIds)->count(),
            'student_count' => $studentCount,
            'students' => $students,
            'pending_submission_count' => (clone $pendingSubmissionQuery)->count(),
            'submissions' => $submissions,
            'homeworks' => $homeworks,
            'attendance_by_status' => $attendanceByStatus,
            'upcoming_exams' => $upcomingExams,
            'courses' => $courses,
            'announcements' => $announcements,
        ];
    }

    private function assignedClassroomIds(User $teacher): Builder
    {
        return Classroom::query()
            ->where('teacher_id', $teacher->id)
            ->where('status', 'active')
            ->select('classrooms.id');
    }
}
