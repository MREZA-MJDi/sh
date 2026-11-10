<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreQuiz24ExamRequest;
use App\Http\Requests\Admin\UpdateExamRequest;
use App\Models\Exam;
use App\Services\Admin\ClassroomWorkspaceService;
use App\Services\Admin\ExamWorkspaceService;
use App\Services\Quiz24\Quiz24Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
class ExamController extends Controller
{
    public function index(ClassroomWorkspaceService $classrooms, ExamWorkspaceService $exams): View
    {
        $user = auth()->user();
        $records = $exams->queryFor($user)->with(['classroom', 'course'])->withCount('results')->latest()->paginate(15);
        $classroomOptions = $classrooms->queryFor($user)->orderBy('name')->get(['id', 'name']);
        $courseOptions = $classrooms->coursesQueryFor($user)->orderBy('title')->get(['id', 'title']);
        return view('admin.exams.index', ['exams' => $records, 'classrooms' => $classroomOptions, 'courses' => $courseOptions]);
    }
    public function update(UpdateExamRequest $request, Exam $exam, Quiz24Service $quiz24, ExamWorkspaceService $workspace): RedirectResponse
    {
        abort_unless($workspace->owns($request->user(), $exam), 403);
        $data = $request->validated();
        $isQuiz24 = $data['provider'] === 'quiz24';
        DB::transaction(function () use ($exam, $data, $isQuiz24, $quiz24): void {
            $exam->update([
                ...$data,
                'integration_status' => $isQuiz24 ? (filled($data['external_id'] ?? null) ? 'awaiting_api_verification' : 'waiting_external_mapping') : 'not_required',
                'integration_note' => $isQuiz24 ? 'اطلاعات آزمون محلی ذخیره شد؛ اعتبار شناسه و همگام‌سازی تا تأیید API انجام نمی‌شود.' : null,
            ]);
            if ($isQuiz24) $quiz24->requestExamProvisioning($exam->refresh(), auth()->user());
        });
        return back()->with('success', $isQuiz24 ? 'آزمون به‌روزرسانی شد؛ درخواست نگاشت Quiz24 در کارتابل ثبت شد.' : 'آزمون به‌روزرسانی شد.');
    }
    public function results(Exam $exam, ExamWorkspaceService $workspace): View
    {
        abort_unless($workspace->owns(auth()->user(), $exam), 403);
        $results = $exam->results()->with('student')->latest('synced_at')->paginate(30);
        return view('admin.exams.results', compact('exam', 'results'));
    }
    public function store(StoreQuiz24ExamRequest $request, Quiz24Service $quiz24): RedirectResponse
    {
        $data = $request->validated();
        $isQuiz24 = $data['provider'] === 'quiz24';
        DB::transaction(function () use ($data, $isQuiz24, $quiz24): void {
            $exam = Exam::create([
                ...$data,
                'created_by' => auth()->id(),
                'integration_status' => $isQuiz24 ? (filled($data['external_id'] ?? null) ? 'awaiting_api_verification' : 'waiting_external_mapping') : 'not_required',
                'integration_note' => $isQuiz24 ? 'آزمون محلی ثبت شد؛ ایجاد یا تأیید آزمون در Quiz24 به قرارداد وب‌سرویس نیاز دارد.' : null,
            ]);
            if ($isQuiz24) $quiz24->requestExamProvisioning($exam, auth()->user());
        });
        return back()->with('success', $isQuiz24 ? 'آزمون ثبت شد و درخواست اتصال Quiz24 در کارتابل قرار گرفت.' : 'آزمون داخلی با موفقیت ثبت شد.');
    }
}
