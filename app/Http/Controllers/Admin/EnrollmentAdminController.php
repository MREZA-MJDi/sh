<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\User;
use App\Services\Courses\CourseEnrollmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class EnrollmentAdminController extends Controller
{
    public function index(Request $request): View
    {
        $query = CourseEnrollment::query()->with(['student:id,name,email,phone', 'course:id,title,learning_features,type,price'])
            ->when($request->filled('course_id'), fn ($q) => $q->where('course_id', $request->integer('course_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')->toString()))
            ->when($request->filled('q'), function ($q) use ($request): void {
                $term = trim($request->string('q')->toString());
                $q->where(fn ($search) => $search
                    ->whereHas('student', fn ($users) => $users->where('name', 'like', "%{$term}%")->orWhere('phone', 'like', "%{$term}%"))
                    ->orWhereHas('course', fn ($courses) => $courses->where('title', 'like', "%{$term}%")));
            });

        $enrollments = $query->latest()->paginate(20)->withQueryString();
        $stats = [
            'active' => CourseEnrollment::where('status', 'active')->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))->count(),
            'expiring' => CourseEnrollment::where('status', 'active')->whereBetween('expires_at', [now(), now()->addDays(30)])->count(),
            'expired' => CourseEnrollment::whereNotNull('expires_at')->where('expires_at', '<=', now())->count(),
            'pending' => CourseEnrollment::whereIn('status', ['pending_payment', 'suspended'])->count(),
        ];
        $students = User::where('is_active', true)->whereHas('roles', fn ($q) => $q->where('name', 'student'))->orderBy('name')->get(['id', 'name']);
        $courses = Course::where('status', 'published')->orderBy('title')->get(['id', 'title']);

        return view('admin.enrollments.index', compact('enrollments', 'stats', 'students', 'courses'));
    }

    public function store(Request $request, CourseEnrollmentService $service): RedirectResponse
    {
        $data = $request->validate([
            'student_id' => ['required', 'integer', 'exists:users,id'],
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ]);
        $student = User::whereKey($data['student_id'])->where('is_active', true)
            ->whereHas('roles', fn ($q) => $q->where('name', 'student'))->firstOrFail();
        $course = Course::whereKey($data['course_id'])->where('status', 'published')->firstOrFail();

        $service->grantManually($student, $course, $request->user(), $data['expires_at'] ?? null);

        return back()->with('success', 'ثبت‌نام دستی ثبت شد؛ هیچ پرداختی به‌صورت ساختگی ایجاد نشده است.');
    }

    public function revoke(CourseEnrollment $enrollment): RedirectResponse
    {
        if ($enrollment->status === 'revoked') {
            return back()->with('status', 'این ثبت‌نام قبلاً لغو شده است.');
        }
        $enrollment->update(['status' => 'revoked']);

        return back()->with('success', 'ثبت‌نام لغو شد و سابقه آن حفظ گردید.');
    }
}
