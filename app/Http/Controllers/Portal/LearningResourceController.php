<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\CourseEnrollment;
use App\Models\LearningResource;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class LearningResourceController extends Controller
{
    public function download(Request $request, LearningResource $resource): StreamedResponse
    {
        $student = $this->studentFor($request);

        $resource = LearningResource::query()
            ->whereKey($resource->getKey())
            ->where('status', 'published')
            ->whereIn('visibility', ['enrolled', 'authenticated'])
            ->whereNotNull('file_path')
            ->firstOrFail();

        // A classroom-scoped resource must require membership in that exact
        // classroom. Course enrollment is an additional condition, not an
        // alternative path that can bypass the classroom restriction.
        if ($resource->classroom_id) {
            $classroom = Classroom::query()
                ->whereKey($resource->classroom_id)
                ->where('status', 'active')
                ->whereHas('students', fn (Builder $students) => $students->whereKey($student->id))
                ->firstOrFail();

            if ($resource->course_id && (int) $resource->course_id !== (int) $classroom->course_id) {
                abort(404);
            }

            $courseId = $resource->course_id ?: $classroom->course_id;
            if ($courseId) {
                abort_unless($this->hasActiveCourseAccess($student, (int) $courseId), 404);
            }
        } elseif ($resource->course_id) {
            // Course-wide material (without a classroom restriction) is
            // available only to currently enrolled students of a published course.
            abort_unless($this->hasActiveCourseAccess($student, (int) $resource->course_id), 404);
        } else {
            abort(404);
        }

        abort_unless(Storage::disk('local')->exists($resource->file_path), 404);

        return Storage::disk('local')->download($resource->file_path);
    }

    private function hasActiveCourseAccess(User $student, int $courseId): bool
    {
        return CourseEnrollment::query()
            ->where('student_id', $student->id)
            ->where('course_id', $courseId)
            ->where('status', 'active')
            ->where(fn (Builder $expiry) => $expiry
                ->whereNull('expires_at')
                ->orWhere('expires_at', '>', now()))
            ->whereHas('course', fn (Builder $course) => $course->where('status', 'published'))
            ->exists();
    }

    private function studentFor(Request $request): User
    {
        $actor = $request->user();

        if ($actor->hasRole('student')) {
            return $actor;
        }

        return $actor->children()
            ->whereKey($request->integer('child'))
            ->where('users.is_active', true)
            ->whereHas('roles', fn (Builder $roles) => $roles->where('name', 'student'))
            ->firstOrFail();
    }
}
