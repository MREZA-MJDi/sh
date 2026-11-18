<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\SpotPlayerLicense;
use App\Models\SpotPlayerVideo;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\BinaryFileResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use App\Services\Learning\LearningAccessService;

final class VideoController extends Controller
{
    public function index(Request $request, LearningAccessService $access): View
    {
        $videos = $this->accessibleVideos($request->user(), $access)
            ->with(['course:id,title,slug', 'classroom:id,name'])
            ->latest('created_at')
            ->paginate(12)
            ->withQueryString();

        return view('student.videos.index', compact('videos'));
    }

    public function show(Request $request, int $video, LearningAccessService $access): View
    {
        $video = $this->findAccessibleVideo($request->user(), $video, $access);

        return view('student.videos.show', compact('video'));
    }

    public function stream(Request $request, int $video, LearningAccessService $access): BinaryFileResponse|Response
    {
        $video = $this->findAccessibleVideo($request->user(), $video, $access);

        // Local staged files can be streamed only after the same enrollment checks
        // used by the video catalogue. External SpotPlayer IDs stay gated by the
        // provider contract; no fake player URL is generated.
        abort_unless(filled($video->file_path) && Storage::disk('local')->exists($video->file_path), 409,
            'این ویدئو هنوز روی سرور محلی آماده پخش نیست و اتصال SpotPlayer نیز فعال نشده است.');

        $path = Storage::disk('local')->path($video->file_path);

        return response()->file($path, [
            'Cache-Control' => 'private, no-store, max-age=0',
            'Pragma' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy' => 'same-origin',
        ]);
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
