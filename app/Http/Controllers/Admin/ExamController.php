<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreQuiz24ExamRequest;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\Exam;
use App\Services\Quiz24\Quiz24Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ExamController extends Controller
{
    public function index(): View
    {
        $exams = Exam::with(['classroom', 'course'])->withCount('results')->latest()->paginate(15);
        $classrooms = Classroom::orderBy('name')->get(['id', 'name']);
        $courses = Course::orderBy('title')->get(['id', 'title']);

        return view('admin.exams.index', compact('exams', 'classrooms', 'courses'));
    }

    public function store(StoreQuiz24ExamRequest $request, Quiz24Service $quiz24): RedirectResponse
    {
        $data = $request->validated();
        $isQuiz24 = $data['provider'] === 'quiz24';

        $exam = DB::transaction(function () use ($data, $isQuiz24, $quiz24): Exam {
            $exam = Exam::create([
                ...$data,
                'created_by' => auth()->id(),
                'integration_status' => $isQuiz24
                    ? (filled($data['external_id'] ?? null) ? 'awaiting_api_verification' : 'waiting_external_mapping')
                    : 'not_required',
                'integration_note' => $isQuiz24
                    ? 'آزمون محلی ثبت شد؛ ایجاد یا تأیید آزمون در Quiz24 به قرارداد وب‌سرویس نیاز دارد.'
                    : null,
            ]);

            if ($isQuiz24) {
                $quiz24->requestExamProvisioning($exam, auth()->user());
            }

            return $exam;
        });

        return back()->with(
            'success',
            $isQuiz24
                ? 'آزمون در شیخان ثبت شد و درخواست ایجاد/نگاشت آن در کارتابل قرار گرفت؛ اتصال Quiz24 تا تحویل API انجام نمی‌شود.'
                : 'آزمون داخلی با موفقیت ثبت شد.'
        );
    }
}
