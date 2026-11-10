<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateQuiz24SettingsRequest;
use App\Models\Exam;
use App\Models\IntegrationRun;
use App\Models\Quiz24Setting;
use App\Models\Quiz24UnmatchedParticipant;
use App\Services\Quiz24\Quiz24Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class Quiz24Controller extends Controller
{
    public function index(Quiz24Service $quiz24): View
    {
        $settings = Quiz24Setting::first();
        $health = $quiz24->health();

        $exams = Exam::with('classroom')
            ->withCount('results')
            ->where('provider', 'quiz24')
            ->latest()->paginate(12);

        $unmatched = Quiz24UnmatchedParticipant::with(['exam', 'resolvedUser'])
            ->where('status', 'unmatched')
            ->latest()->limit(30)->get();

        $operations = IntegrationRun::where('provider', 'quiz24')->latest()->limit(15)->get();

        $students = \App\Models\User::whereHas('roles', fn ($query) => $query->where('name', 'student'))
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

        $settings->fill([
            ...$data,
            // Credentials may be staged, but integration is disabled until the provider contract is approved.
            'is_enabled' => false,
        ])->save();

        return back()->with('success', 'مشخصات اولیه اتصال ذخیره شد؛ Quiz24 تا دریافت قرارداد رسمی API غیرفعال می‌ماند.');
    }

    public function requestExamSync(Quiz24Service $quiz24): RedirectResponse
    {
        $operation = $quiz24->requestExamCatalogSync(requestedBy: auth()->user());

        return back()->with(
            'success',
            'درخواست دریافت فهرست آزمون‌ها ثبت شد؛ وضعیت '.$operation->status.' است و هنوز به Quiz24 متصل نشده‌ایم.'
        );
    }

    public function requestResultsSync(Exam $exam, Quiz24Service $quiz24): RedirectResponse
    {
        $operation = $quiz24->requestResultsSync($exam, auth()->user());

        return back()->with(
            'success',
            'درخواست همگام‌سازی نتایج ثبت شد؛ وضعیت '.$operation->status.' است.'
        );
    }
}
