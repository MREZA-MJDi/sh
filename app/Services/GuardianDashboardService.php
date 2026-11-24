<?php

namespace App\Services;

use App\Models\Announcement;
use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\Consultation;
use App\Models\ExamResult;
use App\Models\Homework;
use App\Models\HomeworkSubmission;
use App\Models\LearningResource;
use App\Models\Message;
use App\Models\ParentMeeting;
use App\Models\User;
use App\Services\Learning\LearningAccessService;
use App\Support\JalaliDate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

final class GuardianDashboardService
{
    public function __construct(private readonly LearningAccessService $learningAccess) {}

    /**
     * Build an access-scoped dashboard for one child linked to the guardian.
     *
     * An unknown child id never broadens access: it falls back to the first
     * active linked child, and an account without linked children receives
     * empty collections.
     */
    public function build(User $guardian, ?int $selectedChildId = null, ?string $attendanceMonthKey = null): array
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
            ->limit(8)
            ->get();

        $homeworkQuery = Homework::query()
            ->whereIn('classroom_id', clone $assignmentClassroomIds)
            ->where('status', 'published');

        $homeworks = (clone $homeworkQuery)
            ->with('classroom:id,name,course_id')
            ->with(['submissions' => fn ($query) => $query
                ->where('student_id', $student->id)
                ->orderByDesc('submitted_at')
                ->with('reviewer:id,name')
                ->limit(1)])
            ->withCount([
                'submissions as selected_student_submission_count' => fn (Builder $query) => $query
                    ->where('student_id', $student->id),
            ])
            ->orderByRaw('CASE WHEN due_at IS NULL THEN 1 ELSE 0 END')
            ->orderBy('due_at')
            ->latest('created_at')
            ->paginate(12, ['*'], 'homework')
            ->withQueryString();

        $currentJalali = JalaliDate::fromGregorian(now());
        $trendStartKey = JalaliDate::monthKeyByOffset($currentJalali['year'], $currentJalali['month'], 5);
        [$trendStartYear, $trendStartMonth] = array_map('intval', explode('-', $trendStartKey));
        $trendStart = JalaliDate::toGregorian($trendStartYear, $trendStartMonth, 1);

        $homeworkTrend = collect();
        (clone $homeworkQuery)
            ->where('created_at', '>=', $trendStart)
            ->with(['submissions' => fn ($query) => $query
                ->where('student_id', $student->id)
                ->orderByDesc('submitted_at')
                ->select(['id', 'homework_id', 'submitted_at'])
                ->limit(1)])
            ->orderBy('id')
            ->chunkById(500, function (Collection $items) use (&$homeworkTrend): void {
                foreach ($items as $homework) {
                    $date = JalaliDate::fromGregorian($homework->created_at);
                    $key = JalaliDate::monthKey($date['year'], $date['month']);
                    $counts = $homeworkTrend->get($key, ['on_time' => 0, 'late' => 0, 'missing' => 0]);
                    $submission = $homework->submissions->first();

                    if ($submission) {
                        if ($homework->due_at && $submission->submitted_at && $submission->submitted_at->gt($homework->due_at)) {
                            $counts['late']++;
                        } else {
                            $counts['on_time']++;
                        }
                    } elseif ($homework->due_at && $homework->due_at->isPast()) {
                        $counts['missing']++;
                    }

                    $homeworkTrend->put($key, $counts);
                }
            });

        $reviewedHomework = HomeworkSubmission::query()
            ->where('student_id', $student->id)
            ->whereNotNull('reviewed_at')
            ->whereHas('homework', fn (Builder $homework) => $homework
                ->where('status', 'published')
                ->whereIn('classroom_id', clone $assignmentClassroomIds))
            ->with([
                'homework:id,title,classroom_id',
                'homework.classroom:id,name',
                'reviewer:id,name',
            ])
            ->latest('reviewed_at')
            ->limit(8)
            ->get();

        $attendanceQuery = Attendance::query()->visibleToStudent($student, clone $classroomIds);
            });

        $attendanceTotal = (clone $attendanceQuery)
            ->whereIn('status', ['present', 'late', 'absent'])
            ->count();

        $attendancePresent = (clone $attendanceQuery)
            ->whereIn('status', ['present', 'late'])
            ->count();

        $attendance = (clone $attendanceQuery)
            ->with(['classroom:id,name', 'session:id,starts_at,ends_at'])
            ->latest('session_date')
            ->limit(8)
            ->get();

        $currentMonth = JalaliDate::fromGregorian(now());
        [$attendanceYear, $attendanceMonth] = $attendanceMonthKey
            ? array_map('intval', explode('-', $attendanceMonthKey))
            : [$currentMonth['year'], $currentMonth['month']];
        $jalaliMonthStart = JalaliDate::toGregorian($attendanceYear, $attendanceMonth, 1);
        $jalaliMonthEnd = JalaliDate::toGregorian(
            $attendanceYear,
            $attendanceMonth,
            JalaliDate::daysInMonth($attendanceYear, $attendanceMonth)
        );

        $attendanceCalendar = (clone $attendanceQuery)
            ->whereBetween('session_date', [$jalaliMonthStart->toDateString(), $jalaliMonthEnd->toDateString()])
            ->orderBy('session_date')
            ->get(['id', 'session_date', 'status']);

        $attendanceBreakdown = (clone $attendanceQuery)
            ->whereIn('status', ['present', 'late', 'absent'])
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');
        $lastLateAttendance = (clone $attendanceQuery)
            ->where('status', 'late')
            ->latest('session_date')
            ->first(['session_date']);

        $resultQuery = ExamResult::query()
            ->where('student_id', $student->id)
            ->whereHas('exam', function (Builder $exam) use ($examClassroomIds, $examCourseIds): void {
                $exam->where('show_to_guardians', true)
                ->where(function (Builder $scope) use ($examClassroomIds, $examCourseIds): void {
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
            });

        $averageExamScore = (clone $resultQuery)
            ->whereNotNull('score')
            ->where('max_score', '>', 0)
            ->selectRaw('AVG((score / max_score) * 20) as aggregate')
            ->value('aggregate');

        $results = (clone $resultQuery)
            ->with(['exam:id,title,classroom_id,course_id', 'exam.course:id,title', 'exam.classroom:id,name'])
            ->latest()
            ->limit(8)
            ->get();

        $examResults = (clone $resultQuery)
            ->with(['exam:id,title,classroom_id,course_id', 'exam.course:id,title', 'exam.classroom:id,name'])
            ->latest()
            ->paginate(12, ['*'], 'results')
            ->withQueryString();

        $resultTrend = collect();
        (clone $resultQuery)
            ->whereNotNull('score')
            ->where('created_at', '>=', $trendStart)
            ->orderBy('id')
            ->chunkById(500, function (Collection $items) use (&$resultTrend): void {
                foreach ($items as $result) {
                    $date = JalaliDate::fromGregorian($result->created_at);
                    $key = JalaliDate::monthKey($date['year'], $date['month']);
                    $score = $result->max_score > 0 ? $result->score / $result->max_score * 20 : null;
                    if ($score === null) continue;
                    $summary = $resultTrend->get($key, ['sum' => 0.0, 'count' => 0]);
                    $summary['sum'] += $score;
                    $summary['count']++;
                    $resultTrend->put($key, $summary);
                }
            });
        $resultTrend = $resultTrend->map(fn (array $summary) => round($summary['sum'] / max(1, $summary['count']), 1));

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
            ->with(['course:id,title', 'classroom:id,name', 'classSession:id,classroom_id,title,starts_at'])
            ->with(['purchases' => fn ($query) => $query->where('student_id', $student->id)->latest()->limit(1)])
            ->latest()
            ->paginate(12, ['id', 'classroom_id', 'class_session_id', 'course_id', 'title', 'description', 'kind', 'is_free', 'price', 'file_path', 'release_at', 'created_at'], 'resources')
            ->withQueryString();

        $courseProgress = app(CourseProgressService::class)->forStudent($student, clone $courseIds, 8);

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

        $meetings = ParentMeeting::query()
            ->forGuardian($guardian->id, $student->id)
            ->with(['classroom:id,name', 'course:id,title'])
            ->orderByRaw("CASE WHEN status IN ('requested', 'proposed') THEN 0 WHEN status = 'scheduled' THEN 1 ELSE 2 END")
            ->orderByRaw('COALESCE(starts_at, preferred_at) ASC')
            ->limit(5)
            ->get();

        $messages = Message::query()
            ->whereNull('parent_id')
            ->where('student_id', $student->id)
            ->where(fn (Builder $participants) => $participants
                ->where('sender_id', $guardian->id)
                ->orWhere('recipient_id', $guardian->id))
            ->with(['sender:id,name', 'recipient:id,name'])
            ->latest()
            ->limit(5)
            ->get();

        return [
            'children' => $children,
            'selected_child' => $selectedChild,
            'classrooms' => $classrooms,
            'course_progress' => $courseProgress,
            'classroom_count' => (clone $classroomIds)->count(),
            'homeworks' => $homeworks,
            'homework_trend' => $homeworkTrend,
            'reviewed_homework' => $reviewedHomework,
            'pending_homework_count' => (clone $homeworkQuery)->whereDoesntHave(
                'submissions',
                fn (Builder $query) => $query->where('student_id', $student->id)
            )->count(),
            'attendance' => $attendance,
            'attendance_calendar' => $attendanceCalendar,
            'attendance_breakdown' => $attendanceBreakdown,
            'last_late_attendance' => $lastLateAttendance,
            'attendance_count' => $attendanceTotal,
            'attendance_rate' => $attendanceTotal > 0
                ? (int) round($attendancePresent / $attendanceTotal * 100)
                : null,
            'results' => $results,
            'average_exam_score' => $averageExamScore,
            'exam_results' => $examResults,
            'result_trend' => $resultTrend,
            'resources' => $resources,
            'resource_count' => (clone $resourceQuery)->count(),
            'announcements' => $announcements,
            'consultations' => $consultations,
            'meetings' => $meetings,
            'messages' => $messages,
        ];
    }

    /**
     * @param  Collection<int, User>  $children
     * @return array<string, mixed>
     */
    private function emptyPaginator(string $pageName): LengthAwarePaginator
    {
        return new LengthAwarePaginator(
            [],
            0,
            12,
            LengthAwarePaginator::resolveCurrentPage($pageName),
            [
                'path' => LengthAwarePaginator::resolveCurrentPath(),
                'pageName' => $pageName,
            ]
        );
    }

    private function emptyDashboard(Collection $children): array
    {
        return [
            'children' => $children,
            'selected_child' => null,
            'classrooms' => collect(),
            'course_progress' => collect(),
            'classroom_count' => 0,
            'homeworks' => $this->emptyPaginator('homework'),
            'homework_trend' => collect(),
            'reviewed_homework' => collect(),
            'pending_homework_count' => null,
            'attendance' => collect(),
            'attendance_calendar' => collect(),
            'attendance_breakdown' => collect(),
            'last_late_attendance' => null,
            'attendance_count' => 0,
            'attendance_rate' => null,
            'results' => collect(),
            'average_exam_score' => null,
            'exam_results' => $this->emptyPaginator('results'),
            'result_trend' => collect(),
            'resources' => $this->emptyPaginator('resources'),
            'resource_count' => 0,
            'announcements' => Announcement::query()
                ->where('status', 'published')
                ->whereIn('audience', ['all', 'parents'])
                ->latest('published_at')
                ->limit(5)
                ->get(['id', 'title', 'body', 'published_at']),
            'consultations' => collect(),
            'meetings' => collect(),
            'messages' => collect(),
        ];
    }
}
