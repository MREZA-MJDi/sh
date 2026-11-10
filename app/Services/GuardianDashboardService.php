<?php

namespace App\Services;

use App\Models\Announcement;
use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\Consultation;
use App\Models\CourseEnrollment;
use App\Models\ExamResult;
use App\Models\Homework;
use App\Models\LearningResource;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class GuardianDashboardService
{
    /**
     * Build an access-scoped dashboard for one child linked to the guardian.
     *
     * An unknown child id never broadens access: it falls back to the first
     * active linked child, and an account without linked children receives
     * empty collections.
     */
    public function build(User $guardian, ?int $selectedChildId = null): array
    {
        $children = $guardian->children()
            ->where('users.is_active', true)
            ->whereHas('roles', fn (Builder $roles) => $roles->where('name', 'student'))
            ->orderBy('users.name')
            ->get(['users.id', 'users.name', 'users.grade']);

        /** @var User|null $selectedChild */
        $selectedChild = $children->firstWhere('id', $selectedChildId) ?? $children->first();

        if (! $selectedChild) {
            return $this->emptyDashboard($children);
        }

        $student = User::query()->findOrFail($selectedChild->id);
        $classroomIds = $this->enrolledClassroomIds($student);
        $courseIds = $this->activeCourseIds($student);

        $classrooms = Classroom::query()
            ->whereIn('classrooms.id', clone $classroomIds)
            ->with(['course:id,title,subject,grade', 'teacher:id,name'])
            ->orderBy('classrooms.name')
            ->limit(8)
            ->get();

        $homeworkQuery = Homework::query()
            ->whereIn('classroom_id', clone $classroomIds)
            ->where('status', 'published');

        $homeworks = (clone $homeworkQuery)
            ->with('classroom:id,name,course_id')
            ->withCount([
                'submissions as selected_student_submission_count' => fn (Builder $query) => $query
                    ->where('student_id', $student->id),
            ])
            ->orderByRaw('CASE WHEN due_at IS NULL THEN 1 ELSE 0 END')
            ->orderBy('due_at')
            ->latest('created_at')
            ->limit(8)
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
            ->limit(8)
            ->get();

        $results = ExamResult::query()
            ->where('student_id', $student->id)
            ->whereHas('exam', function (Builder $exam) use ($classroomIds, $courseIds): void {
                $exam->where('show_to_guardians', true)
                ->where(function (Builder $scope) use ($classroomIds, $courseIds): void {
                    $scope->whereIn('exams.classroom_id', clone $classroomIds)
                        ->orWhere(function (Builder $courseLevel) use ($courseIds): void {
                            $courseLevel
                                ->whereNull('exams.classroom_id')
                                ->whereIn('exams.course_id', clone $courseIds);
                        });
                });
            })
            ->with(['exam:id,title,classroom_id,course_id', 'exam.course:id,title', 'exam.classroom:id,name'])
            ->latest()
            ->limit(8)
            ->get();

        $resourceQuery = LearningResource::query()
            ->whereIn('visibility', ['enrolled', 'authenticated'])
            ->where('status', 'published')
            ->where(function (Builder $scope) use ($classroomIds, $courseIds): void {
                $scope->whereIn('classroom_id', clone $classroomIds)
                    ->orWhereIn('course_id', clone $courseIds);
            });

        $resources = (clone $resourceQuery)
            ->with(['course:id,title', 'classroom:id,name'])
            ->latest()
            ->limit(6)
            ->get(['id', 'classroom_id', 'course_id', 'title', 'description', 'kind', 'is_free', 'price', 'file_path', 'created_at']);

        $announcements = Announcement::query()
            ->where('status', 'published')
            ->whereIn('audience', ['all', 'parents'])
            ->latest('published_at')
            ->limit(5)
            ->get(['id', 'title', 'body', 'published_at']);

        $consultations = Consultation::query()
            ->where('requester_id', $guardian->id)
            ->where('student_id', $student->id)
            ->latest()
            ->limit(5)
            ->get();

        return [
            'children' => $children,
            'selected_child' => $selectedChild,
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
            'resource_count' => (clone $resourceQuery)->count(),
            'announcements' => $announcements,
            'consultations' => $consultations,
        ];
    }

    private function enrolledClassroomIds(User $student): Builder
    {
        return Classroom::query()
            ->where('classrooms.status', 'active')
            ->whereHas('students', fn (Builder $query) => $query->whereKey($student->id))
            ->where(function (Builder $access) use ($student): void {
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

    private function activeCourseIds(User $student): Builder
    {
        return CourseEnrollment::query()
            ->where('student_id', $student->id)
            ->where('status', 'active')
            ->where(fn (Builder $expiry) => $expiry
                ->whereNull('expires_at')
                ->orWhere('expires_at', '>', now()))
            ->whereHas('course', fn (Builder $course) => $course->where('status', 'published'))
            ->select('course_id');
    }

    /**
     * @param  Collection<int, User>  $children
     * @return array<string, mixed>
     */
    private function emptyDashboard(Collection $children): array
    {
        return [
            'children' => $children,
            'selected_child' => null,
            'classrooms' => collect(),
            'classroom_count' => 0,
            'homeworks' => collect(),
            'pending_homework_count' => 0,
            'attendance' => collect(),
            'attendance_count' => 0,
            'attendance_rate' => null,
            'results' => collect(),
            'resources' => collect(),
            'resource_count' => 0,
            'announcements' => Announcement::query()
                ->where('status', 'published')
                ->whereIn('audience', ['all', 'parents'])
                ->latest('published_at')
                ->limit(5)
                ->get(['id', 'title', 'body', 'published_at']),
            'consultations' => collect(),
        ];
    }
}
