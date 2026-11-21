<?php

namespace App\Http\Controllers;

use App\Models\PublicExam;
use App\Models\PublicExamAttempt;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PublicExamController extends Controller
{
    public function index(): View
    {
        $this->ensureEnabled();
        $exams = PublicExam::open()->withCount('questions')->latest()->get();

        return view('public.exams.index', compact('exams'));
    }

    public function show(PublicExam $publicExam): View
    {
        $this->ensureEnabled();
        $this->ensureOpen($publicExam);
        $publicExam->loadCount('questions');

        return view('public.exams.show', ['exam' => $publicExam]);
    }

    public function start(Request $request, PublicExam $publicExam): RedirectResponse
    {
        $this->ensureEnabled();
        $this->ensureOpen($publicExam);

        $digits = ['۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9',
            '٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4','٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9'];
        $request->merge(['phone' => preg_replace('/\D/', '', strtr((string) $request->input('phone'), $digits))]);
        $data = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:60'],
            'phone' => ['required', 'regex:/^09\d{9}$/'],
        ], ['phone.regex' => 'شماره موبایل معتبر نیست؛ نمونه: 09123456789']);

        try {
            $attempt = DB::transaction(function () use ($publicExam, $data, $request) {
                $lockedExam = PublicExam::query()->lockForUpdate()->findOrFail($publicExam->id);
                abort_unless(PublicExam::open()->whereKey($lockedExam->id)->exists(), 404);
                $existing = $lockedExam->attempts()->where('phone', $data['phone'])->lockForUpdate()->first();
                if ($existing) {
                    return $existing;
                }

                $questions = $lockedExam->questions()->with('options')->get();
                abort_if($questions->isEmpty(), 404);

                $snapshotQuestions = $questions->map(function ($question) use ($lockedExam) {
                    $options = $question->options->map(fn ($option) => [
                        'id' => $option->id,
                        'body' => $option->body,
                        'is_correct' => (bool) $option->is_correct,
                    ]);
                    if ($lockedExam->shuffle) {
                        $options = $options->shuffle()->values();
                    }

                    return [
                        'id' => $question->id,
                        'body' => $question->body,
                        'explanation' => $question->explanation,
                        'points' => $question->points,
                        'options' => $options->all(),
                    ];
                });
                if ($publicExam->shuffle) {
                    $snapshotQuestions = $snapshotQuestions->shuffle()->values();
                }

                return $lockedExam->attempts()->create([
                    'name' => trim($data['name']),
                    'phone' => $data['phone'],
                    'started_at' => now(),
                    'expires_at' => now()->addMinutes($lockedExam->duration_minutes),
                    'snapshot' => [
                        'title' => $publicExam->title,
                        'pass_percent' => $lockedExam->pass_percent,
                        'settings' => ['negative_marking' => $lockedExam->negative_marking],
                        'questions' => $snapshotQuestions->all(),
                    ],
                    'ip' => $request->ip(),
                ]);
            });
        } catch (QueryException $exception) {
            // The unique exam/phone index resolves concurrent first-attempt requests.
            $attempt = $publicExam->attempts()->where('phone', $data['phone'])->first();
            if (! $attempt) {
                throw $exception;
            }
        }

        return redirect()->route($attempt->isFinished() ? 'public.exams.result' : 'public.exams.take', $attempt);
    }

    public function take(PublicExamAttempt $attempt): View|RedirectResponse
    {
        if ($attempt->isFinished()) {
            return redirect()->route('public.exams.result', $attempt);
        }
        if ($attempt->isExpired()) {
            DB::transaction(function () use ($attempt): void {
                $locked = PublicExamAttempt::query()->lockForUpdate()->findOrFail($attempt->id);
                $locked->grade($locked->answers ?? []);
            });

            return redirect()->route('public.exams.result', $attempt);
        }

        return view('public.exams.take', compact('attempt'));
    }

    public function save(Request $request, PublicExamAttempt $attempt): JsonResponse
    {
        $data = $request->validate([
            'question_id' => ['required', 'integer'],
            'option_id' => ['required', 'integer'],
        ]);

        DB::transaction(function () use ($attempt, $data): void {
            $locked = PublicExamAttempt::query()->lockForUpdate()->findOrFail($attempt->id);
            abort_if($locked->isFinished() || $locked->isExpired(), 409);

            $question = collect($locked->snapshot['questions'] ?? [])->firstWhere('id', (int) $data['question_id']);
            abort_unless($question, 422);
            $optionIds = collect($question['options'])->pluck('id')->all();
            abort_unless(in_array((int) $data['option_id'], $optionIds, true), 422);

            $answers = $locked->answers ?? [];
            $answers[$data['question_id']] = (int) $data['option_id'];
            $locked->update(['answers' => $answers]);
        });

        return response()->json(['ok' => true]);
    }

    public function submit(Request $request, PublicExamAttempt $attempt): RedirectResponse
    {
        $data = $request->validate([
            'answers' => ['sometimes', 'array', 'max:100'],
            'answers.*' => ['nullable', 'integer'],
        ]);

        DB::transaction(function () use ($attempt, $data): void {
            $locked = PublicExamAttempt::query()->lockForUpdate()->findOrFail($attempt->id);
            if ($locked->isFinished()) {
                return;
            }

            $answers = $locked->isExpired()
                ? ($locked->answers ?? [])
                : array_merge($locked->answers ?? [], $locked->sanitize($data['answers'] ?? []));
            $locked->grade($answers);
        });

        return redirect()->route('public.exams.result', $attempt);
    }

    public function result(PublicExamAttempt $attempt): View|RedirectResponse
    {
        if (! $attempt->isFinished()) {
            return redirect()->route('public.exams.take', $attempt);
        }

        $items = collect($attempt->snapshot['questions'] ?? []);
        $passed = (float) $attempt->score_percent >= (int) data_get($attempt->snapshot, 'pass_percent', 50);

        return view('public.exams.result', compact('attempt', 'items', 'passed'));
    }

    private function ensureEnabled(): void
    {
        abort_unless((bool) config('public_exams.enabled', false), 404);
    }

    private function ensureOpen(PublicExam $exam): void
    {
        abort_unless(PublicExam::open()->whereKey($exam->id)->exists(), 404);
    }
}
