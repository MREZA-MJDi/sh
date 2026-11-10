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
use App\Services\Admin\ClassroomWorkspaceService;
use App\Services\SpotPlayer\SpotPlayerService;
use App\Services\SpotPlayer\SpotPlayerVideoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SpotPlayerController extends Controller
{
    public function index(
        SpotPlayerService $spotPlayer,
        ClassroomWorkspaceService $workspace
    ): View {
        $user = auth()->user();
        $teacherView = $workspace->isTeacher($user);
        $classroomIds = $workspace->queryFor($user)->select('classrooms.id');
        $courseIds = $workspace->coursesQueryFor($user)->select('courses.id');

        $settings = $teacherView ? null : SpotPlayerSetting::query()->first();
        $health = $teacherView ? null : $spotPlayer->health();

        $videosQuery = SpotPlayerVideo::query()->with(['course', 'classroom']);

        if ($teacherView) {
            $videosQuery->where(function ($scope) use ($classroomIds, $courseIds): void {
                $scope->where(function ($classScoped) use ($classroomIds, $courseIds): void {
                    $classScoped->whereNotNull('classroom_id')
                        ->whereIn('classroom_id', $classroomIds)
                        ->whereIn('course_id', $courseIds)
                        ->whereHas('classroom', fn ($classrooms) => $classrooms
                            ->whereColumn('classrooms.course_id', 'spot_player_videos.course_id'));
                })->orWhere(function ($courseScoped) use ($courseIds): void {
                    $courseScoped->whereNull('classroom_id')->whereIn('course_id', $courseIds);
                });
            });
        }

        $videos = $videosQuery->latest()->paginate(10, ['*'], 'videos');

        $courses = $workspace->coursesQueryFor($user)
            ->where('delivery_type', 'spotplayer')
            ->orderBy('title')
            ->get(['id', 'title', 'spotplayer_course_id', 'integration_status']);

        $classrooms = $workspace->queryFor($user)->orderBy('name')->get(['id', 'name']);

        $students = $teacherView
            ? collect()
            : User::query()->whereHas('roles', fn ($query) => $query->where('name', 'student'))
                ->where('is_active', true)->orderBy('name')->get(['id', 'name']);

        $licenses = $teacherView
            ? SpotPlayerLicense::query()->whereRaw('1 = 0')->paginate(10, ['*'], 'licenses')
            : SpotPlayerLicense::with(['student', 'course'])->latest()->paginate(10, ['*'], 'licenses');

        $operations = $teacherView
            ? collect()
            : IntegrationRun::where('provider', 'spotplayer')->latest()->limit(15)->get();

        return view('admin.spotplayer.index', compact(
            'settings', 'health', 'videos', 'licenses', 'courses', 'classrooms',
            'students', 'operations', 'teacherView'
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
            'auto_license_enabled' => false,
            'api_enabled' => false,
        ])->save();

        return back()->with('success', 'تنظیمات محلی ذخیره شد؛ اتصال خارجی تا دریافت قرارداد رسمی فعال نمی‌شود.');
    }

    public function requestCourseSync(SpotPlayerService $spotPlayer): RedirectResponse
    {
        $operation = $spotPlayer->requestCourseCatalogSync();

        return back()->with('success', 'درخواست همگام‌سازی ثبت شد. وضعیت: '.$operation->status.'؛ هیچ درخواست خارجی ارسال نشده است.');
    }

    public function storeVideo(
        StoreSpotPlayerVideoRequest $request,
        SpotPlayerVideoService $videos
    ): RedirectResponse {
        $video = $videos->stage($request->validated(), $request->user());

        $message = $video->file_path
            ? 'فایل ویدیو در فضای خصوصی ذخیره شد؛ انتقال به SpotPlayer پس از تحویل API انجام می‌شود. شناسه داخلی: '.$video->id
            : 'شناسه ویدیوی موجود در SpotPlayer ثبت شد؛ اتصال و نمایش پلیر رسمی پس از تأیید قرارداد سرویس فعال می‌شود. شناسه داخلی: '.$video->id;

        return back()->with('success', $message);
    }

    public function downloadStagedVideo(SpotPlayerVideo $video, ClassroomWorkspaceService $workspace)
    {
        if ($workspace->isTeacher(auth()->user())) {
            if ($video->classroom_id) {
                $classroom = $workspace->queryFor(auth()->user())->find($video->classroom_id);
                abort_unless(
                    $classroom
                    && $video->course_id
                    && (int) $classroom->course_id === (int) $video->course_id
                    && $workspace->coursesQueryFor(auth()->user())->whereKey($video->course_id)->exists(),
                    403
                );
            } else {
                abort_unless(
                    $video->course_id
                    && $workspace->coursesQueryFor(auth()->user())->whereKey($video->course_id)->exists(),
                    403
                );
            }
        }

        abort_unless(filled($video->file_path) && Storage::disk('local')->exists($video->file_path), 404);

        return Storage::disk('local')->download(
            $video->file_path,
            $video->file_original_name ?: 'spotplayer-video'
        );
    }

    public function requestLicense(RequestSpotPlayerLicenseRequest $request, SpotPlayerService $spotPlayer): RedirectResponse
    {
        $data = $request->validated();
        $license = $spotPlayer->requestLicense(
            User::findOrFail($data['student_id']),
            Course::findOrFail($data['course_id']),
            $request->user(),
        );

        return back()->with('success', 'درخواست لایسنس با وضعیت '.$license->status.' ثبت شد؛ کلید یا لینک فعال تا پاسخ واقعی SpotPlayer صادر نمی‌شود.');
    }

    public function requestRevocation(
        RequestSpotPlayerRevocationRequest $request,
        SpotPlayerLicense $license,
        SpotPlayerService $spotPlayer
    ): RedirectResponse {
        $operation = $spotPlayer->requestRevocation($license, $request->user(), $request->validated('reason'));

        return back()->with(
            'success',
            'درخواست ابطال ثبت شد؛ تا تأیید سرویس، لایسنس در وضعیت فعال باقی می‌ماند. شناسه پیگیری: '.$operation->correlation_id
        );
    }
}
