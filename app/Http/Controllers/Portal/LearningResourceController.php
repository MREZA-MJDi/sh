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
            ->where(function (Builder $scope) use ($student): void {
                $scope->whereIn('classroom_id', Classroom::query()
                    ->where('status', 'active')
                    ->whereHas('students', fn (Builder $students) => $students->whereKey($student->id))
                    ->where(function (Builder $access) use ($student): void {
                        $access->whereNull('classrooms.course_id')
                            ->orWhereHas('course', fn (Builder $course) => $course
                                ->where('status', 'published')
                                ->whereHas('enrollments', fn (Builder $enrollments) => $enrollments
                                    ->where('student_id', $student->id)
                                    ->where('status', 'active')
                                    ->where(fn (Builder $expiry) => $expiry
                                        ->whereNull('expires_at')
                                        ->orWhere('expires_at', '>', now()))));
                    })
                    ->select('classrooms.id'))
                    ->orWhereIn('course_id', CourseEnrollment::query()
                        ->where('student_id', $student->id)
                        ->where('status', 'active')
                        ->where(fn (Builder $expiry) => $expiry
                            ->whereNull('expires_at')
                            ->orWhere('expires_at', '>', now()))
                        ->whereHas('course', fn (Builder $course) => $course->where('status', 'published'))
                        ->select('course_id'));
            })
            ->firstOrFail();

        abort_unless(Storage::disk('local')->exists($resource->file_path), 404);

        return Storage::disk('local')->download($resource->file_path);
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
