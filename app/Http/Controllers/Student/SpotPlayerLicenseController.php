<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Services\Integrations\IntegrationException;
use App\Services\SpotPlayer\SpotPlayerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class SpotPlayerLicenseController extends Controller
{
    public function store(Request $request, Course $course, SpotPlayerService $spotPlayer): RedirectResponse
    {
        abort_unless($course->status === 'published' && $course->featureEnabled('recorded_video'), 404);

        try {
            $license = $spotPlayer->requestLicense($request->user(), $course, $request->user());
        } catch (IntegrationException $exception) {
            return back()->with('status', $exception->getMessage());
        }

        if ($license->status === 'waiting_contract') {
            return back()->with('status', $license->error_message ?: 'درخواست ثبت شد و پس از تکمیل اتصال SpotPlayer فعال می‌شود.');
        }

        return back()->with('success', 'درخواست لایسنس ثبت شد؛ کلید فقط پس از پاسخ معتبر سرویس نمایش داده می‌شود.');
    }
}