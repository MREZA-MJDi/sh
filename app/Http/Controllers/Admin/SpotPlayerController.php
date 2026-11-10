<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\SpotPlayerLicense;
use App\Models\SpotPlayerSetting;
use App\Models\SpotPlayerVideo;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SpotPlayerController extends Controller
{
    public function index(): View
    {
        $settings = SpotPlayerSetting::first();
        $configured = (bool) ($settings?->account_id && $settings?->api_key);
        $videos = SpotPlayerVideo::with(['course', 'classroom'])->latest()->paginate(10, ['*'], 'videos');
        $licenses = SpotPlayerLicense::with(['student', 'course'])->latest()->paginate(10, ['*'], 'licenses');
        $courses = Course::orderBy('title')->get(['id', 'title']);
        $classrooms = Classroom::orderBy('name')->get(['id', 'name']);
        $students = User::whereHas('roles', fn ($query) => $query->where('name', 'student'))
            ->where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return view('admin.spotplayer.index', compact(
            'settings', 'configured', 'videos', 'licenses', 'courses', 'classrooms', 'students'
        ));
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'account_id' => ['nullable', 'string', 'max:190'],
            'api_key' => ['nullable', 'string', 'max:5000'],
            'max_devices' => ['required', 'integer', 'between:1,5'],
            'watermark_enabled' => ['nullable', 'boolean'],
            'auto_license_enabled' => ['nullable', 'boolean'],
        ]);

        $settings = SpotPlayerSetting::first() ?? new SpotPlayerSetting();
        if (blank($data['api_key'] ?? null)) {
            unset($data['api_key']);
        }

        $settings->fill([
            ...$data,
            'watermark_enabled' => $request->boolean('watermark_enabled'),
            'auto_license_enabled' => $request->boolean('auto_license_enabled'),
        ])->save();

        return back()->with('success', 'تنظیمات محلی SpotPlayer ذخیره شد؛ اعتبارسنجی اتصال پس از پیاده‌سازی API انجام می‌شود.');
    }

    public function storeVideo(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'course_id' => ['nullable', 'integer', 'exists:courses,id'],
            'classroom_id' => ['nullable', 'integer', 'exists:classrooms,id'],
            'external_id' => ['nullable', 'string', 'max:190'],
            'embed_url' => ['nullable', 'url:https', 'max:2000'],
            'status' => ['required', Rule::in(['draft', 'published', 'processing'])],
        ]);

        SpotPlayerVideo::create($data + ['created_by' => auth()->id()]);

        return back()->with('success', 'اطلاعات ویدیو ثبت شد. انتشار در سرویس خارجی هنوز به اتصال API نیاز دارد.');
    }

    public function updateLicense(Request $request, SpotPlayerLicense $license): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['pending', 'active', 'revoked'])],
            'external_license_id' => ['nullable', 'string', 'max:190', Rule::unique('spot_player_licenses', 'external_license_id')->ignore($license->id)],
            'max_devices' => ['required', 'integer', 'between:1,5'],
        ]);

        $license->update([
            'status' => $data['status'],
            'external_license_id' => $data['external_license_id'] ?? null,
            'max_devices' => $data['max_devices'],
            'issued_at' => $data['status'] === 'active' ? ($license->issued_at ?? now()) : $license->issued_at,
            'revoked_at' => $data['status'] === 'revoked' ? now() : null,
        ]);

        return back()->with('success', 'وضعیت رکورد لایسنس به‌روزرسانی شد. برای اعمال در SpotPlayer باید API سرویس متصل باشد.');
    }
}
