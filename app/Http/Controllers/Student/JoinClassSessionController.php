<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ClassSession;
use App\Models\Classroom;
use App\Services\Learning\LiveClassService;
use App\Services\Learning\LearningAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class JoinClassSessionController extends Controller
{
    public function __invoke(Request $request, ClassSession $classSession, LiveClassService $liveClasses, LearningAccessService $access): RedirectResponse
    {
        $student = $request->user();

        $classroom = Classroom::query()
            ->whereKey($classSession->classroom_id)
            ->whereIn('classrooms.id', $access->activeClassroomIds($student))
            ->firstOrFail();

        abort_unless($classSession->status === 'scheduled' && $classSession->session_type === 'online', 404);

        if (! $classSession->ends_at
            || $classSession->starts_at->gt(now()->addMinutes(15))
            || $classSession->ends_at->lt(now())) {
            return back()->withErrors(['join' => 'ورود به کلاس فقط از ۱۵ دقیقه پیش از شروع تا زمان پایان جلسه فعال است.']);
        }

        $url = $classSession->meeting_url ?: $classroom->meeting_url;
        if (blank($url) && $classSession->meeting_provider === 'skyroom') {
            $url = $liveClasses->joinUrl($student, $classroom);
        }
        $scheme = is_string($url) ? parse_url($url, PHP_URL_SCHEME) : null;
        if (! filter_var($url, FILTER_VALIDATE_URL) || ! in_array($scheme, ['http', 'https'], true)) {
            return back()->withErrors(['join' => 'لینک معتبر ورود برای این جلسه ثبت نشده است.']);
        }

        DB::table('class_session_join_logs')->insert([
            'class_session_id' => $classSession->id,
            'user_id' => $student->id,
            'clicked_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->away($url);
    }
}
