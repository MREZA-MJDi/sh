<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Services\Integrations\IntegrationException;
use App\Services\SpotPlayer\SpotPlayerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class SpotPlayerLicenseController extends Controller
{
    public function store(Request $request, Course $course, SpotPlayerService $spotPlayer): RedirectResponse
    {
        abort_unless($course->delivery_type === 'spotplayer', 404);

        try {
            $license = $spotPlayer->requestLicense($request->user(), $course, $request->user());
        } catch (IntegrationException $exception) {
            throw ValidationException::withMessages(['spotplayer' => $exception->getMessage()]);
        }

        return back()->with(
            'success',
            'درخواست لایسنس با وضعیت «'.$license->status.'» ثبت شد؛ کلید فقط پس از پاسخ معتبر سرویس نمایش داده می‌شود.'
        );
    }
}