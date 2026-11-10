<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\Quiz24Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class Quiz24Controller extends Controller
{
    public function index(): View
    {
        $settings = Quiz24Setting::first();
        $configured = (bool) ($settings?->account_name && $settings?->api_base_url && $settings?->api_token);
        $exams = Exam::with('classroom')->where('provider', 'quiz24')->latest()->paginate(12);

        return view('admin.quiz24.index', compact('settings', 'configured', 'exams'));
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'account_name' => ['nullable', 'string', 'max:190'],
            'api_base_url' => ['nullable', 'url:https', 'max:1000'],
            'api_token' => ['nullable', 'string', 'max:5000'],
            'is_enabled' => ['nullable', 'boolean'],
        ]);

        $settings = Quiz24Setting::first() ?? new Quiz24Setting();
        if (blank($data['api_token'] ?? null)) {
            unset($data['api_token']);
        }

        $settings->fill([
            ...$data,
            'is_enabled' => $request->boolean('is_enabled'),
        ])->save();

        return back()->with('success', 'تنظیمات Quiz24 ذخیره شد؛ اتصال تا زمان تست API تأییدشده محسوب نمی‌شود.');
    }
}
