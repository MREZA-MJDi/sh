<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ResolveQuiz24ParticipantRequest;
use App\Http\Requests\Admin\UpdateQuiz24SettingsRequest;
use App\Models\Exam;
use App\Models\IntegrationRun;
use App\Models\Quiz24Setting;
use App\Models\Quiz24UnmatchedParticipant;
use App\Models\User;
use App\Services\Quiz24\Quiz24Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class Quiz24Controller extends Controller
{
    public function index(Quiz24Service $quiz24): View
    {
        $settings = Quiz24Setting::first();
        $health = $quiz24->health();

        $exams = Exam::with('classroom')->withCount('results')
            ->where('provider', 'quiz24')->latest()->paginate(12);

        $unmatched = Quiz24UnmatchedParticipant::with(['exam', 'resolvedUser'])
            ->whereIn('status', ['unmatched', 'conflict'])
            ->latest()->limit(30)->get();

        $operations = IntegrationRun::where('provider', 'quiz24')->latest()->limit(15)->get();

        $students = User::whereHas('roles', fn ($query) => $query->where('name', 'student'))
            ->where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return view('admin.quiz24.index', compact(
            'settings', 'health', 'exams', 'unmatched', 'operations', 'students'
        ));
    }

    public function updateSettings(UpdateQuiz24SettingsRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $settings = Quiz24Setting::first() ?? new Quiz24Setting();

        if (blank($data['api_token'] ?? null)) {
            unset($data['api_token']);
        }

        $settings->fill([...$data, 'is_enabled' => false])->save();

        return back()->with('success', 'مشخصات اولیه اتصال ذخیره شد؛ Quiz24 تا دریافت قرارداد رسمی API غیرفعال می‌ماند.');
    }

    public function requestExamSync(Quiz24Service $quiz24): RedirectResponse
    {
        $operation = $quiz24->requestExamCatalogSync(auth()->user());

        return back()->with(
            'success',
            'درخواست دریافت فهرست آزمون‌ها ثبت شد؛ وضعیت '.$operation->status.' است و هنوز به Quiz24 متصل نشده‌ایم.'
        );
    }

    public function requestResultsSync(Exam $exam, Quiz24Service $quiz24): RedirectResponse
    {
        $operation = $quiz24->requestResultsSync($exam, auth()->user());

        return back()->with('success', 'درخواست همگام‌سازی نتایج ثبت شد؛ وضعیت '.$operation->status.' است.');
    }

    public function resolveParticipant(
        ResolveQuiz24ParticipantRequest $request,
        Quiz24UnmatchedParticipant $participant,
        Quiz24Service $quiz24
    ): RedirectResponse {
        $student = User::findOrFail($request->validated('student_id'));

        $quiz24->resolveParticipant($participant, $student, $request->user());

        return back()->with('success', 'شرکت‌کننده به پروفایل دانش‌آموز متصل شد و نتیجه در کارنامه ثبت شد.');
    }
}
