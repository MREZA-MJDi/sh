<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\CourseEnrollment;
use App\Models\SpotPlayerVideo;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class VideoController extends Controller
{
    public function index(Request $request): View
    {
        $videos = $this->accessibleVideos($request->user())
            ->with(['course:id,title', 'classroom:id,name'])
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('student.videos.index', compact('videos'));
    }

    public function show(Request $request, int $video): View
    {
        $video = $this->findAccessibleVideo($request->user(), $video);

        abort_unless(filled($video->file_path), 404);

        return view('student.videos.show', compact('video'));
    }

    public function stream(Request $request, int $video): BinaryFileResponse
    {
        $video = $this->findAccessibleVideo($request->user(), $video);

        abort_unless(
            filled($video->file_path)
            && Storage::disk('local')->exists($video->file_path)
            && str_starts_with((string) $video->file_mime_type, 'video/'),
            404
        );

        $path = Storage::disk('local')->path($video->file_path);

        return response()->file($path, [
            'Content-Type' => $video->file_mime_type,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store, max-age=0',
            'Accept-Ranges' => 'bytes',
        ]);
    }

    private function findAccessibleVideo(User $student, int $videoId): SpotPlayerVideo
    {
        return $this->accessibleVideos($student)
            ->with(['course:id,title', 'classroom:id,name'])
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
                ->whereIn('courses.id', clone $activeCourseIds))
            ->where(function (Builder $scope) use ($student): void {
                $scope->whereNull('classroom_id')
                    ->orWhereHas('classroom', fn (Builder $classroom) => $classroom
                        ->where('status', 'active')
                        ->whereHas('students', fn (Builder $students) => $students->whereKey($student->id)));
            });
    }
}
