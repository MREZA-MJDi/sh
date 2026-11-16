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
    public function index(Request $request): RedirectResponse
    {
        return redirect()->route('student.courses.index');
    }

    public function show(Request $request, int $video): RedirectResponse
    {
        $video = $this->findAccessibleVideo($request->user(), $video);

        return redirect()->route('public.courses.show', $video->course->slug);
    }

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
            ->currentlyActive()
            ->select('course_id');

        return SpotPlayerVideo::query()
            ->where(function (Builder $delivery) {
                $delivery->whereNotNull('file_path')->orWhereNotNull('external_id');
            })
            ->whereHas('course', fn (Builder $course) => $course
                ->where('status', 'published')
                ->where('delivery_type', 'spotplayer')
                ->whereIn('courses.id', clone $activeCourseIds))
            ->where(function (Builder $scope) use ($student): void {
                $scope->whereNull('classroom_id')
                    ->orWhereHas('classroom', fn (Builder $classroom) => $classroom
                        ->where('status', 'active')
                        ->whereColumn('classrooms.course_id', 'spot_player_videos.course_id')
                        ->whereHas('students', fn (Builder $students) => $students
                            ->whereKey($student->id)));
            });
    }
}
