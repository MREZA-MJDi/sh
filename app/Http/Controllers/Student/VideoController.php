<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\SpotPlayerLicense;
use App\Models\SpotPlayerVideo;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use App\Services\Learning\LearningAccessService;

final class VideoController extends Controller
{
    public function index(Request $request): RedirectResponse
    {
        return redirect()->route('student.courses.index');
    }

    public function show(Request $request, int $video, LearningAccessService $access): RedirectResponse
    {
        $video = $this->findAccessibleVideo($request->user(), $video, $access);

        return redirect()->route('public.courses.show', $video->course->slug);
    }

    public function stream(Request $request, int $video, LearningAccessService $access): Response
    {
        $this->findAccessibleVideo($request->user(), $video, $access);

        return response(
            'فایل خام ویدئوی آموزشی ارائه نمی‌شود. پخش فقط از طریق پلیر محافظت‌شده SpotPlayer پس از فعال‌شدن اتصال سرویس ممکن است.',
            409
        )->header('Cache-Control', 'private, no-store, max-age=0')
            ->header('X-Content-Type-Options', 'nosniff');
    }

    private function findAccessibleVideo(User $student, int $videoId, LearningAccessService $access): SpotPlayerVideo
    {
        return $this->accessibleVideos($student, $access)
            ->with(['course:id,title,slug,delivery_type,spotplayer_course_id', 'classroom:id,name'])
            ->findOrFail($videoId);
    }

    private function accessibleVideos(User $student, LearningAccessService $access): Builder
    {
        $activeCourseIds = $access->activeCourseIds($student, 'recorded_video');
        $activeClassroomIds = $access->activeClassroomIds($student, 'recorded_video');

        return SpotPlayerVideo::query()
            ->where(function (Builder $delivery) {
                $delivery->whereNotNull('file_path')->orWhereNotNull('external_id');
            })
            ->whereHas('course', fn (Builder $course) => $course
                ->where('status', 'published')
                ->where(function (Builder $videoDelivery): void {
                    $videoDelivery->where(function (Builder $legacy): void {
                            $legacy->whereNull('learning_features')
                                ->where('delivery_type', 'spotplayer');
                        })->orWhere('learning_features->recorded_video', true);
                })
                ->whereIn('courses.id', clone $activeCourseIds))
            ->where(function (Builder $scope) use ($student, $activeClassroomIds): void {
                $scope->whereNull('classroom_id')
                    ->orWhere(function (Builder $classScope) use ($activeClassroomIds): void {
                        $classScope->whereIn('classroom_id', clone $activeClassroomIds)
                            ->whereHas('classroom', fn (Builder $classroom) => $classroom
                                ->whereColumn('classrooms.course_id', 'spot_player_videos.course_id'));
                    });
            });
    }
}
