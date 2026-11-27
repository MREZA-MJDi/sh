<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class CourseTeaserController extends Controller
{
    public function __invoke(string $slug): BinaryFileResponse
    {
        $course = Course::query()->where('slug', $slug)->where('status', 'published')->firstOrFail();

        return self::video($course);
    }

    public static function video(Course $course): BinaryFileResponse
    {
        $disk = Storage::disk('local');
        abort_unless($course->teaser_status === 'ready' && filled($course->teaser_path) && $disk->exists($course->teaser_path), 404);

        return response()->file($disk->path($course->teaser_path), [
            'Content-Type' => 'video/mp4',
            'Content-Disposition' => 'inline',
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
            'Cross-Origin-Resource-Policy' => 'same-origin',
        ])->setPrivate();
    }
}
