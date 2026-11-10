<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RequestSpotPlayerLicenseRequest;
use App\Http\Requests\Admin\RequestSpotPlayerRevocationRequest;
use App\Http\Requests\Admin\StoreSpotPlayerVideoRequest;
use App\Http\Requests\Admin\UpdateSpotPlayerSettingsRequest;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\IntegrationRun;
use App\Models\SpotPlayerLicense;
use App\Models\SpotPlayerSetting;
use App\Models\SpotPlayerVideo;
use App\Models\User;
use App\Services\Integrations\IntegrationOperationService;
use App\Services\SpotPlayer\SpotPlayerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SpotPlayerController extends Controller
{
    public function index(SpotPlayerService $spotPlayer): View
    {
        $settings = SpotPlayerSetting::first();
        $health = $spotPlayer->health();

        $videos = SpotPlayerVideo::with(['course', 'classroom'])->latest()->paginate(10, ['*'], 'videos');
        $licenses = SpotPlayerLicense::with(['student', 'course'])->latest()->paginate(10, ['*'], 'licenses');
        $courses = Course::where('delivery_type', 'spotplayer')->orderBy('title')->get(['id', 'title', 'spotplayer_course_id', 'integration_status']);
        $classrooms = Classroom::orderBy('name')->get(['id', 'name']);
        $students = User::whereHas('roles', fn ($query) => $query->where('name', 'student'))
            ->where('is_active', true)->orderBy('name')->get(['id', 'name']);

        $operations = IntegrationRun::where('provider', 'spotplayer')->latest()->limit(15)->get();

        return view('admin.spotplayer.index', compact(
            'settings', 'health', 'videos', 'licenses', 'courses',
            'classrooms', 'students', 'operations'
        ));
    }

    public function updateSettings(UpdateSpotPlayerSettingsRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $settings = SpotPlayerSetting::first() ?? new SpotPlayerSetting();

        if (blank($data['api_key'] ?? null)) {
            unset($data['api_key']);
        }

        $settings->fill([
            ...$data,
            'watermark_enabled' => $request->boolean('watermark_enabled'),
            // Until the provider contract is installed, automatic remote actions stay disabled.
            'auto_license_enabled' => false,
            'api_enabled' => false,
        ])->save();

        return back()->with('success', 'تنظیمات محلی ذخیره شد؛ اتصال خارجی تا دریافت و پیاده‌سازی قرارداد رسمی فعال نمی‌شود.');
    }

    public function requestCourseSync(SpotPlayerService $spotPlayer): RedirectResponse
    {
        $operation = $spotPlayer->requestCourseCatalogSync();

        return back()->with(
            'success',
            'درخواست همگام‌سازی در کارتابل ثبت شد. وضعیت: '.$operation->status.'؛ هنوز هیچ درخواست خارجی ارسال نشده است.'
        );
    }

    public function storeVideo(
        StoreSpotPlayerVideoRequest $request,
        IntegrationOperationService $operations
    ): RedirectResponse {
        $data = $request->validated();

        $video = SpotPlayerVideo::create([
            ...$data,
            'status' => 'waiting_contract',
            'created_by' => auth()->id(),
        ]);

        $operations->waitForContract(
            provider: 'spotplayer',
            action: 'register_video',
            entity: $video,
            safeRequestMeta: [
                'video_id' => $video->id,
                'local_course_id' => $video->course_id,
                'local_classroom_id' => $video->classroom_id,
                'external_video_id_supplied' => filled($video->external_id),
            ],
            idempotencyKey: 'spotplayer:register-video:'.$video->id,
        );

        return back()->with('success', 'اطلاعات ویدیو در شیخان ذخیره شد؛ ارسال به SpotPlayer پس از تحویل API انجام می‌شود.');
    }

    public function requestLicense(
        RequestSpotPlayerLicenseRequest $request,
        SpotPlayerService $spotPlayer
    ): RedirectResponse {
        $data = $request->validated();

        $license = $spotPlayer->requestLicense(
            User::findOrFail($data['student_id']),
            Course::findOrFail($data['course_id']),
            $request->user(),
        );

        return back()->with(
            'success',
            'درخواست لایسنس با وضعیت '.$license->status.' ثبت شد؛ کلید یا لینک فعال تا پاسخ واقعی SpotPlayer صادر نمی‌شود.'
        );
    }

    public function requestRevocation(
        RequestSpotPlayerRevocationRequest $request,
        SpotPlayerLicense $license,
        SpotPlayerService $spotPlayer
    ): RedirectResponse {
        $operation = $spotPlayer->requestRevocation(
            $license,
            $request->user(),
            $request->validated('reason'),
        );

        return back()->with(
            'success',
            'درخواست ابطال ثبت شد؛ تا تأیید سرویس، لایسنس در وضعیت فعال باقی می‌ماند. شناسه پیگیری: '.$operation->correlation_id
        );
    }
}
