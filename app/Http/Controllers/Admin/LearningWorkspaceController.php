<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class LearningWorkspaceController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['draft', 'published', 'archived'])],
        ]);

        $courses = Course::query()
            ->with('teacher:id,name')
            ->withCount('sections')
            ->when($filters['q'] ?? null, fn ($query, $term) => $query->where('title', 'like', '%'.$term.'%'))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->latest('id')
            ->paginate(18)
            ->withQueryString();

        return view('admin.lms.index', compact('courses', 'filters'));
    }

    public function show(Request $request, Course $course): View
    {
        $actor = $request->user();
        $course->loadMissing('teacher:id,name')->loadCount('sections');

        $classrooms = $actor->hasAnyPermission(['classes.manage', 'classes.manage.assigned', 'classes.view.assigned'])
            ? $course->classrooms()->withCount('students')->orderBy('name')->orderBy('id')->paginate(12)
            : null;

        $links = [
            ['label' => 'محتوا و ضبط کلاس', 'route' => 'admin.courses.edit', 'parameters' => ['course' => $course], 'icon' => 'video'],
        ];
        if ($actor->hasPermissionTo('users.manage')) {
            $links[] = ['label' => 'ثبت‌نام‌های این دوره', 'route' => 'admin.enrollments.index', 'parameters' => ['course_id' => $course->id], 'icon' => 'user'];
        }
        if ($actor->hasAnyPermission(['resources.manage', 'resources.manage.assigned'])) {
            $links[] = ['label' => 'منابع این دوره', 'route' => 'admin.resources.index', 'parameters' => ['course_id' => $course->id], 'icon' => 'lib'];
        }

        if ($actor->hasAnyPermission(['exams.manage', 'exams.manage.assigned'])) {
            $links[] = ['label' => 'آزمون‌های این دوره', 'route' => 'admin.exams.index', 'parameters' => ['course_id' => $course->id], 'icon' => 'exam'];
        }

        $canViewHomework = $actor->hasAnyPermission(['homework.manage.all', 'homework.manage.assigned', 'homework.view.assigned']);

        return view('admin.lms.show', compact('course', 'classrooms', 'links', 'canViewHomework'));
    }
}
