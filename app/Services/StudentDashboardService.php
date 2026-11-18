<?php

namespace App\Services;

use App\Models\Announcement;
use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\ClassSession;
use App\Models\ExamResult;
use App\Models\Homework;
use App\Models\LearningResource;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use App\Services\Learning\LearningAccessService;

final class StudentDashboardService
{
    public function __construct(private readonly LearningAccessService $learningAccess) {}

    public function build(User $student): array
    {
        $classroomIds = $this->learningAccess->activeClassroomIds($student);
        $courseIds = $this->learningAccess->activeCourseIds($student);
        $assignmentClassroomIds = $this->learningAccess->activeClassroomIds($student, 'assignments');
        $examClassroomIds = $this->learningAccess->activeClassroomIds($student, 'exams');
        $examCourseIds = $this->learningAccess->activeCourseIds($student, 'exams');
        $resourceClassroomIds = $this->learningAccess->activeClassroomIds($student, 'resources');
        $resourceCourseIds = $this->learningAccess->activeCourseIds($student, 'resources');

        $classrooms = Classroom::query()
            ->whereIn('classrooms.id', clone $classroomIds)
            ->with(['course:id,title,subject,grade', 'teacher:id,name'])
            ->orderBy('classrooms.name')
            ->limit(6)
            ->get();

        $upcomingSessions = ClassSession::query()
            ->whereIn('classroom_id', clone $classroomIds)
            ->where('status', 'scheduled')
            ->whereNotNull('ends_at')
            ->where('ends_at', '>=', now())
            ->with('classroom:id,name,meeting_url,skyroom_room_id,skyroom_status')
            ->orderBy('starts_at')
            ->limit(10)
            ->get();

        $homeworkQuery = Homework::query()
            ->whereIn('classroom_id', clone $assignmentClassroomIds)
            ->where('status', 'published');

        $homeworks = (clone $homeworkQuery)
            ->with('classroom:id,name,course_id')
            ->with([
                'submissions' => fn ($query) => $query
                    ->where('student_id', $student->id)
                    ->with('files'),
            ])
            ->withCount([
                'submissions as submitted_by_student_count' => fn (Builder $query) => $query
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
            ->with(['classroom:id,name', 'session:id,starts_at,ends_at'])
            ->latest('session_date')
            ->limit(6)
            ->get();

        $results = ExamResult::query()
            ->where('student_id', $student->id)
            ->whereHas('exam', function (Builder $exam) use ($examClassroomIds, $examCourseIds): void {
                $exam->where(function (Builder $scope) use ($examClassroomIds, $examCourseIds): void {
                    $scope->where(function (Builder $classScoped) use ($examClassroomIds): void {
                        $classScoped->whereIn('exams.classroom_id', clone $examClassroomIds)
                            ->where(function (Builder $classCourse): void {
                                $classCourse->whereHas('classroom', fn (Builder $classroom) => $classroom
                                    ->whereColumn('classrooms.course_id', 'exams.course_id'))
                                    ->orWhere(function (Builder $classOnly): void {
                                        $classOnly->whereNull('exams.course_id')
                                            ->whereHas('classroom', fn (Builder $classroom) => $classroom
                                                ->whereNull('classrooms.course_id'));
                                    });
                            });
                    })->orWhere(function (Builder $courseLevel) use ($examCourseIds): void {
                        $courseLevel->whereNull('exams.classroom_id')
                            ->whereIn('exams.course_id', clone $examCourseIds);
                    });
                });
            })
            ->with(['exam:id,title,classroom_id,course_id', 'exam.classroom:id,name', 'exam.course:id,title'])
            ->latest()
            ->limit(6)
            ->get();

        $resourceQuery = LearningResource::query()
            ->whereIn('visibility', ['public', 'enrolled', 'authenticated'])
            ->where('status', 'published')
            ->where(fn (Builder $release) => $release->whereNull('release_at')->orWhere('release_at', '<=', now()))
            ->where(function (Builder $scope) use ($resourceClassroomIds, $resourceCourseIds): void {
                $scope->where(function (Builder $classScoped) use ($resourceClassroomIds, $resourceCourseIds): void {
                    $classScoped->whereNotNull('classroom_id')
                        ->whereIn('classroom_id', clone $resourceClassroomIds)
                        ->where(function (Builder $courseScope) use ($resourceCourseIds): void {
                            $courseScope->whereNull('course_id')
                                ->orWhere(function (Builder $matchingCourse) use ($resourceCourseIds): void {
                                    $matchingCourse->whereIn('course_id', clone $resourceCourseIds)
                                        ->whereHas('classroom', fn (Builder $classroom) => $classroom
                                            ->whereColumn('classrooms.course_id', 'learning_resources.course_id'));
                                });
                        });
                })->orWhere(function (Builder $courseOnly) use ($resourceCourseIds): void {
                    $courseOnly->whereNull('classroom_id')->whereIn('course_id', clone $resourceCourseIds);
                })->orWhere(function (Builder $unscoped): void {
                    $unscoped->whereNull('classroom_id')->whereNull('course_id')
                        ->whereIn('visibility', ['public', 'authenticated']);
                });
            });

        $resources = (clone $resourceQuery)
            ->with(['course:id,title', 'classroom:id,name'])
            ->latest()
            ->limit(5)
            ->get(['id', 'classroom_id', 'course_id', 'title', 'kind', 'is_free', 'price', 'file_path', 'downloadable', 'release_at', 'created_at']);

        $courseProgress = app(CourseProgressService::class)->forStudent($student, clone $courseIds, 6);

        $announcements = Announcement::query()
            ->where('status', 'published')
            ->whereIn('audience', ['all', 'students'])
            ->latest('published_at')
            ->limit(4)
            ->get(['id', 'title', 'body', 'published_at']);

        return [
            'classrooms' => $classrooms,
            'upcoming_sessions' => $upcomingSessions,
            'course_progress' => $courseProgress,
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
            'notes' => $student->studentNotes()->latest()->limit(8)->get(),
        ];
    }

    /**
     * Fetch only the data required by the assignments page.
     *
     * @return LengthAwarePaginator<int, Homework>
     */
    public function assignments(User $student): LengthAwarePaginator
    {
        $classroomIds = $this->learningAccess->activeClassroomIds($student, 'assignments');

        return Homework::query()
            ->whereIn('classroom_id', clone $classroomIds)
            ->where('status', 'published')
            ->with([
                'classroom:id,name,course_id',
                'submissions' => fn ($query) => $query
                    ->where('student_id', $student->id)
                    ->with('files'),
            ])
            ->withCount([
                'submissions as submitted_by_student_count' => fn (Builder $query) => $query
                    ->where('student_id', $student->id),
            ])
            ->orderByRaw('CASE WHEN due_at IS NULL THEN 1 ELSE 0 END')
            ->orderBy('due_at')
            ->latest('created_at')
            ->paginate(12)
            ->withQueryString();
    }

    /**
     * Focused result data for the student results page.
     */
    public function results(User $student)
    {
        return $this->visibleExamResults($student, 50);
    }

    public function submittedHomeworkCount(User $student): int
    {
        $classroomIds = $this->learningAccess->activeClassroomIds($student, 'assignments');

        return Homework::query()
            ->whereIn('classroom_id', clone $classroomIds)
            ->where('status', 'published')
            ->whereHas('submissions', fn (Builder $query) => $query->where('student_id', $student->id))
            ->count();
    }

    /**
     * Resource catalogue is paginated independently from dashboard previews.
     */
    public function resources(User $student): LengthAwarePaginator
    {
        $classroomIds = $this->learningAccess->activeClassroomIds($student, 'resources');
        $courseIds = $this->learningAccess->activeCourseIds($student, 'resources');

        return LearningResource::query()
            ->whereIn('visibility', ['public', 'enrolled', 'authenticated'])
            ->where('status', 'published')
            ->where(fn (Builder $release) => $release->whereNull('release_at')->orWhere('release_at', '<=', now()))
            ->where(function (Builder $scope) use ($classroomIds, $courseIds): void {
                $scope->where(function (Builder $classScoped) use ($classroomIds, $courseIds): void {
                    $classScoped->whereNotNull('classroom_id')
                        ->whereIn('classroom_id', clone $classroomIds)
                        ->where(function (Builder $courseScope) use ($courseIds): void {
                            $courseScope->whereNull('course_id')
                                ->orWhere(function (Builder $matchingCourse) use ($courseIds): void {
                                    $matchingCourse->whereIn('course_id', clone $courseIds)
                                        ->whereHas('classroom', fn (Builder $classroom) => $classroom
                                            ->whereColumn('classrooms.course_id', 'learning_resources.course_id'));
                                });
                        });
                })->orWhere(function (Builder $courseOnly) use ($courseIds): void {
                    $courseOnly->whereNull('classroom_id')->whereIn('course_id', clone $courseIds);
                })->orWhere(function (Builder $unscoped): void {
                    $unscoped->whereNull('classroom_id')->whereNull('course_id')
                        ->whereIn('visibility', ['public', 'authenticated']);
                });
            })
            ->with(['course:id,title', 'classroom:id,name'])
            ->latest()
            ->paginate(12)
            ->withQueryString();
    }

    public function liveClasses(User $student): LengthAwarePaginator
    {
        $classroomIds = $this->learningAccess->activeClassroomIds($student);

        return ClassSession::query()
            ->whereIn('classroom_id', clone $classroomIds)
            ->where('session_type', 'online')
            ->where('status', 'scheduled')
            ->whereNotNull('ends_at')
            ->where('ends_at', '>=', now())
            ->with('classroom:id,name,meeting_url,skyroom_room_id,skyroom_status')
            ->orderBy('starts_at')
            ->paginate(12)
            ->withQueryString();
    }

    public function achievements(User $student): array
    {
        $classroomIds = $this->learningAccess->activeClassroomIds($student);
        $courseIds = $this->learningAccess->activeCourseIds($student);

        $attendance = Attendance::query()
            ->where('student_id', $student->id)
            ->whereIn('classroom_id', clone $classroomIds);
        $attendanceTotal = (clone $attendance)->whereIn('status', ['present', 'late', 'absent'])->count();
        $attendancePresent = (clone $attendance)->whereIn('status', ['present', 'late'])->count();

        return [
            'course_progress' => app(CourseProgressService::class)->forStudent($student, clone $courseIds, 6),
            'attendance_rate' => $attendanceTotal > 0
                ? (int) round($attendancePresent / $attendanceTotal * 100)
                : null,
            'results' => $this->visibleExamResults($student, 50),
        ];
    }

    private function visibleExamResults(User $student, int $limit)
    {
        $examClassroomIds = $this->learningAccess->activeClassroomIds($student, 'exams');
        $examCourseIds = $this->learningAccess->activeCourseIds($student, 'exams');

        return ExamResult::query()
            ->where('student_id', $student->id)
            ->whereHas('exam', function (Builder $exam) use ($examClassroomIds, $examCourseIds): void {
                $exam->where(function (Builder $scope) use ($examClassroomIds, $examCourseIds): void {
                    $scope->where(function (Builder $classScoped) use ($examClassroomIds): void {
                        $classScoped->whereIn('exams.classroom_id', clone $examClassroomIds)
                            ->where(function (Builder $classCourse): void {
                                $classCourse->whereHas('classroom', fn (Builder $classroom) => $classroom
                                    ->whereColumn('classrooms.course_id', 'exams.course_id'))
                                    ->orWhere(function (Builder $classOnly): void {
                                        $classOnly->whereNull('exams.course_id')
                                            ->whereHas('classroom', fn (Builder $classroom) => $classroom
                                                ->whereNull('classrooms.course_id'));
                                    });
                            });
                    })->orWhere(function (Builder $courseLevel) use ($examCourseIds): void {
                        $courseLevel->whereNull('exams.classroom_id')
                            ->whereIn('exams.course_id', clone $examCourseIds);
                    });
                });
            })
            ->with(['exam:id,title,classroom_id,course_id', 'exam.classroom:id,name', 'exam.course:id,title'])
            ->latest()
            ->limit($limit)
            ->get();
    }

}
