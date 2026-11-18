<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\SpotPlayerVideo;
use App\Models\User;
use App\Services\Learning\LearningAccessService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

final class VideoController extends Controller
{
    public function index(Request $request, LearningAccessService $access): RedirectResponse|View
    {
        $videos = $this->accessibleVideos($request->user(), $access)
            ->where('status', '!=', 'waiting_contract')
            ->where(function (Builder $delivery): void {
                $delivery->whereNotNull('external_id')
                    ->orWhereNotNull('embed_url')
                    ->orWhere(function (Builder $local): void {
                        $local->where('status', 'active')->whereNotNull('file_path');
                    });
            })
            ->with(['course:id,title,slug', 'classroom:id,name'])
            ->latest('created_at')
            ->paginate(12)
            ->withQueryString();

        if ($videos->total() === 0) {
            return redirect()->route('student.courses.index');
        }

        return view('student.videos.index', compact('videos'));
    }

    public function show(Request $request, int $video, LearningAccessService $access): RedirectResponse|View
    {
        $video = $this->findAccessibleVideo($request->user(), $video, $access);

        // Staged files and IDs waiting for the provider contract are not playable
        // student-facing recordings; keep the student on the existing course page.
        if (
            $video->status === 'waiting_contract'
            || (blank($video->external_id) && blank($video->embed_url) && ! ($video->status === 'active' && filled($video->file_path)))
        ) {
            return redirect()->route('public.courses.show', $video->course->slug);
        }

        return view('student.videos.show', compact('video'));
    }

    public function stream(Request $request, int $video, LearningAccessService $access): Response
    {
        $recording = $this->findAccessibleVideo($request->user(), $video, $access);

        abort_unless(
            $recording->status === 'active'
                && filled($recording->file_path)
                && blank($recording->external_id)
                && Storage::disk('local')->exists($recording->file_path),
            409,
            'این ویدیو هنوز برای پخش مستقیم آماده نیست.'
        );

        return response()->file(Storage::disk('local')->path($recording->file_path), [
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
            ->where(function (Builder $delivery): void {
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
            ->where(function (Builder $scope) use ($activeClassroomIds): void {
                $scope->whereNull('classroom_id')
                    ->orWhere(function (Builder $classScope) use ($activeClassroomIds): void {
                        $classScope->whereIn('classroom_id', clone $activeClassroomIds)
                            ->whereHas('classroom', fn (Builder $classroom) => $classroom
                                ->whereColumn('classrooms.course_id', 'spot_player_videos.course_id'));
                    });
            });
    }
}