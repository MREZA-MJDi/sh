<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\CourseEnrollment;
use App\Models\SpotPlayerLicense;
use App\Models\SpotPlayerVideo;
use App\Models\User;
use App\Services\SpotPlayer\SpotPlayerService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

final class VideoController extends Controller
{
    public function index(Request $request, SpotPlayerService $spotPlayer): View
    {
        $videos = $this->accessibleVideos($request->user())
            ->with(['course:id,title', 'classroom:id,name'])
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $health = $spotPlayer->health();

        return view('student.videos.index', compact('videos', 'health'));
    }

    public function show(Request $request, int $video, SpotPlayerService $spotPlayer): View
    {
        $video = $this->findAccessibleVideo($request->user(), $video);
        $license = SpotPlayerLicense::query()
            ->where('student_id', $request->user()->id)
            ->where('course_id', $video->course_id)
            ->where('status', 'active')
            ->first();

        return view('student.videos.show', [
            'video' => $video,
            'license' => $license,
            'health' => $spotPlayer->health(),
            'playbackReady' => false,
        ]);
    }

    /**
     * Raw staged media is never a student delivery path. Playback must be
     * supplied by the authenticated SpotPlayer web player after the provider
     * adapter, course mapping and active student license have been verified.
     */
    public function stream(Request $request, int $video): Response
    {
        $this->findAccessibleVideo($request->user(), $video);

        return response(
            'فایل خام ویدئوی آموزشی ارائه نمی‌شود. پخش فقط از طریق پلیر محافظت‌شده SpotPlayer پس از فعال‌شدن اتصال سرویس ممکن است.',
            409
        )->header('Cache-Control', 'private, no-store, max-age=0')
            ->header('X-Content-Type-Options', 'nosniff');
    }

    private function findAccessibleVideo(User $student, int $videoId): SpotPlayerVideo
    {
        return $this->accessibleVideos($student)
            ->with(['course:id,title,delivery_type,spotplayer_course_id', 'classroom:id,name'])
            ->findOrFail($videoId);
    }

    private function accessibleVideos(User $student): Builder
    {
        $activeCourseIds = CourseEnrollment::query()
            ->where('student_id', $student->id)
            ->where('status', 'active')
            ->where(fn (Builder $expiry) => $expiry
                ->whereNull('expires_at')
                ->orWhere('expires_at', '>', now()))
            ->select('course_id');

        return SpotPlayerVideo::query()
            ->whereNotNull('file_path')
            ->whereHas('course', fn (Builder $course) => $course
                ->where('status', 'published')
                ->where('delivery_type', 'spotplayer')
                ->whereIn('courses.id', clone $activeCourseIds))
            ->where(function (Builder $scope) use ($student): void {
                $scope->whereNull('classroom_id')
                    ->orWhereHas('classroom', fn (Builder $classroom) => $classroom
                        ->where('status', 'active')
                        ->whereHas('students', fn (Builder $students) => $students
                            ->whereKey($student->id)));
            });
    }
}
