<?php

namespace App\Http\Controllers\Learning;

use App\Enums\CourseType;
use App\Http\Controllers\Controller;
use App\Models\ClassSession;
use App\Models\Course;
use App\Services\Learning\CourseAccess;
use App\Services\Learning\LiveClassService;
use Illuminate\Http\Request;

class JoinClassController extends Controller
{
    public function __invoke(Request $request, string $slug, CourseAccess $access, LiveClassService $live)
    {
        $course = Course::published()->where('slug', $slug)->firstOrFail();

        abort_unless($course->type === CourseType::OnlineClass, 404);
        abort_unless($access->state($request->user(), $course)['unlocked'], 403);

        $open = ClassSession::where('course_id', $course->id)
            ->where('starts_at', '<=', now()->addMinutes(15))
            ->where('ends_at', '>=', now())
            ->exists();

        if (! $open) {
            return back()->withErrors(['join' => 'الان کلاسی برگزار نمی‌شود.']);
        }

        return redirect()->away($live->joinUrl($request->user(), $course));
    }
}
