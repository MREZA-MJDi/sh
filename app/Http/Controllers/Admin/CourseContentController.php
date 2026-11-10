<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseSection;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Services\Admin\ClassroomWorkspaceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class CourseContentController extends Controller
{
    public function show(int $course, ClassroomWorkspaceService $workspace): View
    {
        $course = $workspace->coursesQueryFor(request()->user())
            ->with(['sections.lessons'])
            ->findOrFail($course);

        return view('admin.courses.content', compact('course'));
    }

    public function storeSection(Request $request, int $course, ClassroomWorkspaceService $workspace): RedirectResponse
    {
        $course = $workspace->coursesQueryFor($request->user())->findOrFail($course);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:5000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'status' => ['required', 'in:draft,published,archived'],
        ]);

        $course->sections()->create($data);

        return back()->with('success', 'بخش دوره ایجاد شد.');
    }

    public function updateSection(Request $request, CourseSection $section, ClassroomWorkspaceService $workspace): RedirectResponse
    {
        $this->assertSectionAccess($request, $section, $workspace);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:5000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'status' => ['required', 'in:draft,published,archived'],
        ]);

        $section->update($data);

        return back()->with('success', 'بخش دوره به‌روزرسانی شد.');
    }

    public function destroySection(Request $request, CourseSection $section, ClassroomWorkspaceService $workspace): RedirectResponse
    {
        $this->assertSectionAccess($request, $section, $workspace);

        if (LessonProgress::query()->whereHas('lesson', fn ($query) => $query->where('course_section_id', $section->id))->exists()) {
            throw ValidationException::withMessages([
                'section' => 'برای درس‌های این بخش سابقه پیشرفت وجود دارد؛ به‌جای حذف، بخش را بایگانی کنید.',
            ]);
        }

        $section->delete();

        return back()->with('success', 'بخش دوره حذف شد.');
    }

    public function storeLesson(Request $request, CourseSection $section, ClassroomWorkspaceService $workspace): RedirectResponse
    {
        $this->assertSectionAccess($request, $section, $workspace);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'body' => ['nullable', 'string', 'max:60000'],
            'duration_minutes' => ['nullable', 'integer', 'min:0', 'max:600'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'status' => ['required', 'in:draft,published,archived'],
        ]);

        $section->lessons()->create($data);

        return back()->with('success', 'درس به بخش اضافه شد.');
    }

    public function updateLesson(Request $request, Lesson $lesson, ClassroomWorkspaceService $workspace): RedirectResponse
    {
        $this->assertLessonAccess($request, $lesson, $workspace);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'body' => ['nullable', 'string', 'max:60000'],
            'duration_minutes' => ['nullable', 'integer', 'min:0', 'max:600'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'status' => ['required', 'in:draft,published,archived'],
        ]);

        $lesson->update($data);

        return back()->with('success', 'درس به‌روزرسانی شد.');
    }

    public function destroyLesson(Request $request, Lesson $lesson, ClassroomWorkspaceService $workspace): RedirectResponse
    {
        $this->assertLessonAccess($request, $lesson, $workspace);

        if ($lesson->progress()->exists()) {
            throw ValidationException::withMessages([
                'lesson' => 'برای این درس سابقه پیشرفت دانش‌آموز ثبت شده است؛ درس را بایگانی کنید.',
            ]);
        }

        $lesson->delete();

        return back()->with('success', 'درس حذف شد.');
    }

    private function assertSectionAccess(Request $request, CourseSection $section, ClassroomWorkspaceService $workspace): void
    {
        abort_unless(
            $workspace->coursesQueryFor($request->user())->whereKey($section->course_id)->exists(),
            404
        );
    }

    private function assertLessonAccess(Request $request, Lesson $lesson, ClassroomWorkspaceService $workspace): void
    {
        abort_unless(
            $workspace->coursesQueryFor($request->user())
                ->whereHas('sections', fn ($query) => $query->whereKey($lesson->course_section_id))
                ->exists(),
            404
        );
    }
}
