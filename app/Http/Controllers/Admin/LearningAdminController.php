<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\Learning\IssueSpotPlayerLicense;
use App\Jobs\Learning\SendLessonVideoToProvider;
use App\Models\Course;
use App\Models\CourseLesson;
use App\Models\CourseLicense;
use App\Models\VideoUploadSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LearningAdminController extends Controller
{
    public function teaser(Course $course): StreamedResponse
    {
        abort_unless($course->teaser_status === 'ready' && filled($course->teaser_path), 404);

        $response = Storage::disk('public')->response($course->teaser_path);
        $response->headers->set('Cache-Control', 'private, no-store, max-age=0');
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        return $response;
    }

    public function teaserStatus(Course $course): JsonResponse
    {
        return response()->json(['status' => $course->teaser_status, 'error' => $course->teaser_error]);
    }

    public function destroyTeaser(Course $course)
    {
        if ($course->teaser_status === 'processing') {
            return back()->withErrors(['teaser' => 'تا پایان پردازش، حذف تیزر مجاز نیست.']);
        }

        VideoUploadSession::query()->where('target', 'teaser')->where('target_id', $course->id)
            ->where('status', 'uploading')->get()->each(function (VideoUploadSession $session): void {
                Storage::disk('local')->deleteDirectory('chunks/'.$session->upload_id);
                $session->update(['status' => 'cancelled']);
            });

        foreach (['teaser_path', 'teaser_poster_path'] as $col) {
            if ($course->{$col}) {
                Storage::disk('public')->delete($course->{$col});
            }
        }
        if ($course->teaser_original_path) {
            Storage::disk('local')->delete($course->teaser_original_path);
        }

        $course->update([
            'teaser_path' => null, 'teaser_poster_path' => null, 'teaser_original_path' => null,
            'teaser_status' => 'none', 'teaser_error' => null,
        ]);

        return back()->with('status', 'تیزر حذف شد.');
    }

    /** تلاش دوباره صدور لایسنس (نه فعال‌سازی دستی؛ وضعیت فقط با پاسخ معتبر سرویس عوض می‌شود). */
    public function retryLicense(CourseLicense $license)
    {
        abort_unless(in_array($license->status, [CourseLicense::FAILED, CourseLicense::WAITING_CONTRACT], true), 422);

        $license->update(['status' => CourseLicense::QUEUED, 'attempts' => 0, 'last_error' => null]);
        IssueSpotPlayerLicense::dispatch($license->id)->afterCommit();

        return back()->with('status', 'درخواست صدور لایسنس دوباره در صف قرار گرفت.');
    }

    public function retryLessonVideo(CourseLesson $lesson)
    {
        abort_unless($lesson->video_upload_path && in_array($lesson->video_status, ['failed', 'waiting_contract'], true), 422);

        $lesson->update(['video_status' => 'sending', 'video_error' => null]);
        SendLessonVideoToProvider::dispatch($lesson->id)->afterCommit();

        return back()->with('status', 'ارسال ویدیو دوباره در صف قرار گرفت.');
    }
}
