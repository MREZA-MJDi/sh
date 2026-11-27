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
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

final class VideoController extends Controller
{
    public function index(Request $request, LearningAccessService $access): View
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
            ->with(['course:id,title,slug', 'classroom:id,name', 'classSession:id,classroom_id,title,starts_at'])
            ->latest('created_at')
            ->paginate(12)
            ->withQueryString();

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

    public function stream(Request $request, int $video, LearningAccessService $access): StreamedResponse|Response
    {
        $recording = $this->findAccessibleVideo($request->user(), $video, $access);

        $privacyHeaders = [
            'Cache-Control' => 'private, no-store, max-age=0',
            'Pragma' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy' => 'same-origin',
            'Cross-Origin-Resource-Policy' => 'same-origin',
            'X-Robots-Tag' => 'noindex, nofollow',
        ];

        // Fetch Metadata is a browser-level deterrent, not DRM or authentication.
        // Keep compatibility with older browsers that omit these headers.
        if ($request->header('Sec-Fetch-Mode') === 'navigate'
            || in_array($request->header('Sec-Fetch-Dest'), ['document', 'iframe', 'frame', 'object', 'embed'], true)
            || in_array($request->header('Sec-Fetch-Site'), ['cross-site', 'same-site'], true)) {
            return response('برای تماشای ویدیو از پلیر دوره استفاده کنید.', 403, $privacyHeaders);
        }

        if (
            $recording->status !== 'active'
            || blank($recording->file_path)
            || filled($recording->external_id)
            || ! Storage::disk('local')->exists($recording->file_path)
        ) {
            return response(
                'پخش این فایل تا زمان آماده‌شدن منبع ویدیو فعال نیست.',
                409,
                $privacyHeaders
            );
        }

        $mimeType = str_starts_with((string) $recording->file_mime_type, 'video/')
            ? $recording->file_mime_type
            : 'application/octet-stream';

        $path = Storage::disk('local')->path($recording->file_path);
        $size = filesize($path);
        abort_if($size === false || $size < 1, 404);

        $start = 0;
        $end = $size - 1;
        $status = 200;
        $headers = [
            'Content-Type' => $mimeType,
            'Accept-Ranges' => 'bytes',
            'Content-Disposition' => 'inline',
            ...$privacyHeaders,
        ];

        // PHP authorizes the request; Nginx sends the bytes and handles ranges.
        // Only canonical processed recording paths may be internally redirected.
        if (config('learning.nginx_private_video')
            && preg_match('~^private/course-videos/[A-Za-z0-9_-]+\\.mp4$~D', $recording->file_path)
            && realpath($path) === realpath(Storage::disk('local')->path('private/course-videos')).DIRECTORY_SEPARATOR.basename($path)) {
            return response('', 200, [
                ...$headers,
                'X-Accel-Redirect' => '/_protected_course_video/'.rawurlencode(basename($path)),
            ]);
        }

        $range = $request->header('Range');
        if (filled($range)) {
            if (! preg_match('/^bytes=(\\d*)-(\\d*)$/', trim($range), $matches) || ($matches[1] === '' && $matches[2] === '')) {
                return response('', 416, ['Content-Range' => 'bytes */'.$size, ...$headers]);
            }

            if ($matches[1] === '') {
                $suffixLength = (int) $matches[2];
                if ($suffixLength < 1) {
                    return response('', 416, ['Content-Range' => 'bytes */'.$size, ...$headers]);
                }
                $start = max(0, $size - $suffixLength);
            } else {
                $start = (int) $matches[1];
                $end = $matches[2] === '' ? $size - 1 : (int) $matches[2];
            }

            if ($start >= $size || $end < $start) {
                return response('', 416, ['Content-Range' => 'bytes */'.$size, ...$headers]);
            }

            $end = min($end, $size - 1);
            $status = 206;
            $headers['Content-Range'] = "bytes {$start}-{$end}/{$size}";
        }

        $length = $end - $start + 1;
        $headers['Content-Length'] = (string) $length;

        return response()->stream(function () use ($path, $start, $length): void {
            $handle = fopen($path, 'rb');
            if ($handle === false) {
                return;
            }

            try {
                fseek($handle, $start);
                $remaining = $length;
                while ($remaining > 0 && ! feof($handle)) {
                    $chunk = fread($handle, min(1024 * 64, $remaining));
                    if ($chunk === false || $chunk === '') {
                        break;
                    }
                    echo $chunk;
                    $remaining -= strlen($chunk);
                    if (ob_get_level() > 0) {
                        ob_flush();
                    }
                    flush();
                }
            } finally {
                fclose($handle);
            }
        }, $status, $headers);
    }

    private function findAccessibleVideo(User $student, int $videoId, LearningAccessService $access): SpotPlayerVideo
    {
        return $this->accessibleVideos($student, $access)
            ->with(['course:id,title,slug,delivery_type,spotplayer_course_id', 'classroom:id,name', 'classSession:id,classroom_id,title,starts_at'])
            ->findOrFail($videoId);
    }

    private function accessibleVideos(User $student, LearningAccessService $access): Builder
    {
        $activeCourseIds = $access->activeCourseIds($student, 'recorded_video');
        $activeClassroomIds = $access->activeClassroomIds($student, 'recorded_video');

        return SpotPlayerVideo::query()
            ->where('approval_status', 'approved')
            ->where(function (Builder $delivery): void {
                $delivery->whereNotNull('file_path')->orWhereNotNull('external_id');
            })
            ->whereIn('course_id', $activeCourseIds)
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
