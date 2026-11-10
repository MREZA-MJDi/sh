<?php

namespace App\Services;

use App\Models\CourseEnrollment;
use App\Models\Exam;
use App\Models\ExamAnswer;
use App\Models\ExamAttempt;
use App\Models\ExamResult;
use App\Models\Question;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class StudentExamService
{
    public function query(User $student): Builder
    {
        $courseIds = $this->activeCourseIds($student);

        return Exam::query()
            ->where('provider', 'internal')
            ->where('status', 'published')
            ->where(function (Builder $access) use ($student, $courseIds): void {
                $access->where(function (Builder $courseScope) use ($courseIds, $student): void {
                    $courseScope->whereNotNull('course_id')
                        ->whereIn('course_id', clone $courseIds)
                        ->where(function (Builder $classScope) use ($student): void {
                            $classScope->whereNull('classroom_id')
                                ->orWhereHas('classroom', fn (Builder $classroom) => $classroom
                                    ->where('status', 'active')
                                    ->whereHas('students', fn (Builder $students) => $students->whereKey($student->id)));
                        });
                })->orWhere(function (Builder $classOnly) use ($student): void {
                    $classOnly->whereNotNull('classroom_id')
                        ->whereHas('classroom', fn (Builder $classroom) => $classroom
                            ->whereNull('course_id')
                            ->where('status', 'active')
                            ->whereHas('students', fn (Builder $students) => $students->whereKey($student->id)));
                });
            })
            ->with(['course:id,title', 'classroom:id,name'])
            ->withCount('questions')
            ->with(['attempts' => fn ($attempts) => $attempts
                ->where('student_id', $student->id)
                ->latest('attempt_number')])
            ->orderByRaw('CASE WHEN starts_at IS NULL THEN 0 ELSE 1 END')
            ->orderBy('starts_at')
            ->orderByDesc('id');
    }

    public function canAccess(User $student, Exam $exam): bool
    {
        return $this->accessibleQuery($student, ['published', 'closed'])
            ->whereKey($exam->id)
            ->exists();
    }

    public function find(User $student, Exam $exam): Exam
    {
        abort_unless($this->canAccess($student, $exam), 404);

        return $exam->load([
            'course:id,title',
            'classroom:id,name',
            'questions' => fn ($questions) => $questions->orderBy('sort_order')->orderBy('id'),
            'attempts' => fn ($attempts) => $attempts
                ->where('student_id', $student->id)
                ->with('answers')
                ->latest('attempt_number'),
        ]);
    }

    public function start(User $student, Exam $exam): ExamAttempt
    {
        return DB::transaction(function () use ($student, $exam): ExamAttempt {
            $lockedExam = Exam::query()->lockForUpdate()->findOrFail($exam->id);
            abort_unless($this->canAccess($student, $lockedExam), 404);

            if ($lockedExam->status !== 'published'
                || ($lockedExam->starts_at && $lockedExam->starts_at->isFuture())) {
                throw ValidationException::withMessages(['exam' => 'این آزمون در حال حاضر برای شروع باز نیست.']);
            }

            if (! $lockedExam->questions()->exists()) {
                throw ValidationException::withMessages(['exam' => 'هنوز سؤالی برای این آزمون ثبت نشده است.']);
            }

            $active = ExamAttempt::query()
                ->where('exam_id', $lockedExam->id)
                ->where('student_id', $student->id)
                ->where('status', 'in_progress')
                ->latest('attempt_number')
                ->first();

            if ($active) {
                return $active;
            }

            $usedAttempts = ExamAttempt::query()
                ->where('exam_id', $lockedExam->id)
                ->where('student_id', $student->id)
                ->whereIn('status', ['graded', 'pending_review', 'submitted'])
                ->count();

            if ($usedAttempts >= max(1, min(5, (int) $lockedExam->attempts_allowed))) {
                throw ValidationException::withMessages(['exam' => 'تعداد دفعات مجاز این آزمون به پایان رسیده است.']);
            }

            return ExamAttempt::query()->create([
                'exam_id' => $lockedExam->id,
                'student_id' => $student->id,
                'attempt_number' => $usedAttempts + 1,
                'started_at' => now(),
                'status' => 'in_progress',
            ]);
        }, 3);
    }

    public function submit(User $student, ExamAttempt $attempt, array $answers): ExamAttempt
    {
        return DB::transaction(function () use ($student, $attempt, $answers): ExamAttempt {
            $locked = ExamAttempt::query()
                ->with(['exam.questions'])
                ->lockForUpdate()
                ->findOrFail($attempt->id);

            abort_unless((int) $locked->student_id === (int) $student->id, 404);
            abort_unless($locked->status === 'in_progress', 422);
            abort_unless($this->canAccess($student, $locked->exam), 404);

            if ($locked->exam->status !== 'published') {
                throw ValidationException::withMessages(['exam' => 'این آزمون توسط مدرس بسته شده است. پاسخ ثبت نشد.']);
            }

            $deadline = (int) $locked->exam->duration_minutes > 0
                ? $locked->started_at->copy()->addMinutes((int) $locked->exam->duration_minutes)
                : null;

            if ($deadline && now()->gt($deadline->copy()->addSeconds(15))) {
                throw ValidationException::withMessages(['exam' => 'مهلت پاسخ‌گویی به آزمون به پایان رسیده است.']);
            }

            $questionIds = $locked->exam->questions->modelKeys();
            $unknownIds = array_diff(array_map('intval', array_keys($answers)), $questionIds);
            if ($unknownIds !== []) {
                throw ValidationException::withMessages(['answers' => 'پاسخ برای سؤال نامعتبر ارسال شده است.']);
            }

            $total = 0.0;
            $maxScore = 0.0;
            $manualReview = false;

            foreach ($locked->exam->questions as $question) {
                $maxScore += (float) $question->score;
                $answer = $answers[$question->id] ?? null;
                $serialized = $this->serializeAnswer($answer);

                if ($question->type === 'text') {
                    $manualReview = true;
                    $correct = null;
                    $earned = null;
                } else {
                    $correct = $this->matches($question, $answer);
                    $earned = $correct ? (float) $question->score : 0.0;
                    $total += $earned;
                }

                ExamAnswer::query()->updateOrCreate(
                    ['exam_attempt_id' => $locked->id, 'question_id' => $question->id],
                    ['answer' => $serialized, 'is_correct' => $correct, 'score' => $earned],
                );
            }

            $locked->forceFill([
                'submitted_at' => now(),
                'score' => $manualReview ? null : round($total, 2),
                'max_score' => round($maxScore, 2),
                'status' => $manualReview ? 'pending_review' : 'graded',
            ])->save();

            if (! $manualReview) {
                $this->storeResult($locked, $total, $maxScore);
            }

            return $locked->load(['exam', 'answers.question']);
        }, 3);
    }

    public function recordManualReview(ExamAttempt $attempt, User $reviewer, array $scores, ?string $feedback): ExamAttempt
    {
        return DB::transaction(function () use ($attempt, $reviewer, $scores, $feedback): ExamAttempt {
            $locked = ExamAttempt::query()
                ->with(['exam.questions', 'answers.question'])
                ->lockForUpdate()
                ->findOrFail($attempt->id);

            abort_unless($locked->status === 'pending_review', 422);
            $total = 0.0;
            $maxScore = 0.0;

            foreach ($locked->exam->questions as $question) {
                $maxScore += (float) $question->score;
                $answer = $locked->answers->firstWhere('question_id', $question->id);

                if (! $answer) {
                    continue;
                }

                if ($question->type === 'text') {
                    if (! array_key_exists((string) $answer->id, $scores)
                        && ! array_key_exists($answer->id, $scores)) {
                        throw ValidationException::withMessages(['scores' => 'برای همه پاسخ‌های تشریحی باید نمره ثبت شود.']);
                    }

                    $score = (float) ($scores[$answer->id] ?? $scores[(string) $answer->id]);
                    if ($score < 0 || $score > (float) $question->score) {
                        throw ValidationException::withMessages(['scores' => 'نمره واردشده خارج از محدوده سؤال است.']);
                    }

                    $answer->update(['score' => $score, 'is_correct' => null]);
                }

                $total += (float) ($answer->fresh()->score ?? 0);
            }

            $locked->forceFill([
                'score' => round($total, 2),
                'max_score' => round($maxScore, 2),
                'status' => 'graded',
                'feedback' => $feedback,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
            ])->save();

            $this->storeResult($locked, $total, $maxScore, $feedback);

            return $locked->refresh();
        }, 3);
    }

    private function storeResult(ExamAttempt $attempt, float $score, float $maxScore, ?string $feedback = null): void
    {
        ExamResult::query()->updateOrCreate(
            ['exam_id' => $attempt->exam_id, 'student_id' => $attempt->student_id],
            [
                'score' => round($score, 2),
                'max_score' => round($maxScore, 2),
                'source_data' => ['provider' => 'internal', 'attempt_id' => $attempt->id],
                'synced_at' => now(),
                'feedback' => $feedback,
            ]
        );
    }

    private function matches(Question $question, mixed $answer): bool
    {
        $expected = $this->normalize($question->correct_answer, $question->type);
        $actual = $this->normalizeAnswer($answer, $question->type);

        return $expected !== null && $actual !== null && $expected === $actual;
    }

    private function normalize(?string $value, string $type): mixed
    {
        if ($value === null) {
            return null;
        }

        return $this->normalizeAnswer($value, $type);
    }

    private function normalizeAnswer(mixed $value, string $type): mixed
    {
        if ($type === 'multiple') {
            if (is_string($value)) {
                $decoded = json_decode($value, true);
                $value = is_array($decoded) ? $decoded : preg_split('/\s*,\s*/', $value);
            }

            $items = array_map('strval', is_array($value) ? $value : [$value]);
            sort($items);

            return array_values(array_unique($items));
        }

        if (is_array($value)) {
            $value = reset($value);
        }

        return $value === null ? null : trim((string) $value);
    }

    private function serializeAnswer(mixed $answer): ?string
    {
        if ($answer === null || $answer === '' || $answer === []) {
            return null;
        }

        return is_array($answer)
            ? json_encode(array_values($answer), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)
            : (string) $answer;
    }

    private function accessibleQuery(User $student, array $statuses): Builder
    {
        $courseIds = $this->activeCourseIds($student);

        return Exam::query()
            ->where('provider', 'internal')
            ->whereIn('status', $statuses)
            ->where(function (Builder $access) use ($student, $courseIds): void {
                $access->where(function (Builder $courseScope) use ($courseIds, $student): void {
                    $courseScope->whereNotNull('course_id')
                        ->whereIn('course_id', clone $courseIds)
                        ->where(function (Builder $classScope) use ($student): void {
                            $classScope->whereNull('classroom_id')
                                ->orWhereHas('classroom', fn (Builder $classroom) => $classroom
                                    ->where('status', 'active')
                                    ->whereHas('students', fn (Builder $students) => $students->whereKey($student->id)));
                        });
                })->orWhere(function (Builder $classOnly) use ($student): void {
                    $classOnly->whereNotNull('classroom_id')
                        ->whereHas('classroom', fn (Builder $classroom) => $classroom
                            ->whereNull('course_id')
                            ->where('status', 'active')
                            ->whereHas('students', fn (Builder $students) => $students->whereKey($student->id)));
                });
            });
    }

    private function activeCourseIds(User $student)
    {
        return CourseEnrollment::query()
            ->where('student_id', $student->id)
            ->where('status', 'active')
            ->where(fn ($expiry) => $expiry->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->whereHas('course', fn ($course) => $course->where('status', 'published'))
            ->select('course_id');
    }
}
