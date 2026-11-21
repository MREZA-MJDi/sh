<?php

namespace App\Http\Controllers;

use App\Models\PublicExam;
use App\Models\PublicExamAttempt;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
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
        ], [
            'phone.regex' => 'شماره موبایل معتبر نیست؛ نمونه: 09123456789',
        ]);

        $attempt = DB::transaction(function () use ($publicExam, $data, $request) {
            $existing = $publicExam->attempts()->where('phone', $data['phone'])->lockForUpdate()->first();
            if ($existing) {
                return $existing;
            }

            $questions = $publicExam->questions()->with('options')->get();
            abort_if($questions->isEmpty(), 404);

            $snapshotQuestions = $questions->map(function ($question) use ($publicExam) {
                $options = $question->options->map(fn ($option) => [
                    'id' => $option->id,
                    'body' => $option->body,
                    'is_correct' => (bool) $option->is_correct,
                ]);
                if ($publicExam->shuffle) {
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

            return $publicExam->attempts()->create([
                'name' => trim($data['name']),
                'phone' => $data['phone'],
                'started_at' => now(),
                'expires_at' => now()->addMinutes($publicExam->duration_minutes),
                'snapshot' => [
                    'title' => $publicExam->title,
                    'pass_percent' => $publicExam->pass_percent,
                    'settings' => ['negative_marking' => $publicExam->negative_marking],
                    'questions' => $snapshotQuestions->all(),
                ],
                'ip' => $request->ip(),
            ]);
        });

        return redirect()->route($attempt->isFinished() ? 'public.exams.result' : 'public.exams.take', $attempt);
    }

    public function take(PublicExamAttempt $attempt): View|RedirectResponse
    {
        $this->ensureEnabled();
        if ($attempt->isFinished()) {
            return redirect()->route('public.exams.result', $attempt);
        }
        if ($attempt->isExpired()) {
            $attempt->grade($attempt->answers ?? []);

            return redirect()->route('public.exams.result', $attempt);
        }

        return view('public.exams.take', compact('attempt'));
    }

    public function save(Request $request, PublicExamAttempt $attempt): JsonResponse
    {
        $this->ensureEnabled();
        abort_if($attempt->isFinished() || $attempt->isExpired(), 409);
        $data = $request->validate([
            'question_id' => ['required', 'integer'],
            'option_id' => ['nullable', 'integer'],
        ]);

        $allowed = collect($attempt->snapshot['questions'] ?? [])->firstWhere('id', (int) $data['question_id']);
        abort_unless($allowed, 422);
        $optionIds = collect($allowed['options'])->pluck('id')->all();
        abort_if($data['option_id'] !== null && ! in_array((int) $data['option_id'], $optionIds, true), 422);

        $answers = $attempt->answers ?? [];
        if ($data['option_id'] === null) {
            unset($answers[$data['question_id']]);
        } else {
            $answers[$data['question_id']] = (int) $data['option_id'];
        }
        $attempt->update(['answers' => $answers]);

        return response()->json(['ok' => true]);
    }

    public function submit(Request $request, PublicExamAttempt $attempt): RedirectResponse
    {
        $this->ensureEnabled();
        if (! $attempt->isFinished()) {
            $data = $request->validate(['answers' => ['sometimes', 'array', 'max:100'], 'answers.*' => ['nullable', 'integer']]);
            $answers = $attempt->isExpired()
                ? ($attempt->answers ?? [])
                : array_merge($attempt->answers ?? [], $attempt->sanitize($data['answers'] ?? []));
            $attempt->grade($answers);
        }

        return redirect()->route('public.exams.result', $attempt);
    }

    public function result(PublicExamAttempt $attempt): View|RedirectResponse
    {
        $this->ensureEnabled();
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
