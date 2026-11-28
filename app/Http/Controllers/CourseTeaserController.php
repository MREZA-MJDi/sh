<?php

namespace App\Http\Controllers;

use App\Jobs\Learning\RenderViewerVideo;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class CourseTeaserController extends Controller
{
    public function __invoke(Request $request, string $slug): BinaryFileResponse|Response
    {
        abort_unless($request->user()?->is_active, 403);
        $course = Course::query()->where('slug', $slug)->where('status', 'published')->firstOrFail();

        return self::video($course);
    }

    public static function video(Course $course): BinaryFileResponse|Response
    {
        $disk = Storage::disk('local');
        abort_unless($course->teaser_status === 'ready' && filled($course->teaser_path) && $disk->exists($course->teaser_path), 404);

        $viewer = request()->user();
        abort_unless($viewer?->is_active, 403);
        abort_if(request()->header('Sec-Fetch-Mode') === 'navigate', 403);
        $path = RenderViewerVideo::readyPath($course->teaser_path, $viewer);
        abort_if($path === null, 503, 'نسخه اختصاصی ویدیو در حال آماده‌سازی است.', ['Retry-After' => '3', 'Cache-Control' => 'private, no-store']);

        if (config('learning.nginx_private_video')) {
            return response('', 200, [
                'Content-Type' => 'video/mp4',
                'Content-Disposition' => 'inline',
                'Cache-Control' => 'private, no-store, max-age=0',
                'X-Content-Type-Options' => 'nosniff',
                'Cross-Origin-Resource-Policy' => 'same-origin',
                'X-Accel-Redirect' => '/_protected_viewer_video/'.basename($path),
            ]);
        }

        return response()->file($path, [
            'Content-Type' => 'video/mp4',
            'Content-Disposition' => 'inline',
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
            'Cross-Origin-Resource-Policy' => 'same-origin',
        ])->setPrivate();
    }
}
