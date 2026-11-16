<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Services\Courses\CourseEnrollmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class EnrollFreeCourseController extends Controller
{
    public function __invoke(Request $request, Course $course, CourseEnrollmentService $enrollments): RedirectResponse
    {
        $enrollments->enrollFreeCourse($request->user(), $course);

        return redirect()
            ->route('public.courses.show', $course->slug)
            ->with('success', 'ثبت‌نام رایگان انجام شد؛ دوره به فهرست دوره‌های شما اضافه شد.');
    }
}
