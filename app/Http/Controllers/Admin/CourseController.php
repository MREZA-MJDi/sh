<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CourseController extends Controller
{
    public function index(): View
    {
        $courses = Course::with('teacher')->withCount('classrooms')->latest()->paginate(12);
        $teachers = User::whereHas('roles', fn ($query) => $query->where('name', 'teacher'))
            ->where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return view('admin.courses.index', compact('courses', 'teachers'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:10000'],
            'subject' => ['nullable', 'string', 'max:100'],
            'grade' => ['nullable', 'string', 'max:50'],
            'teacher_id' => ['nullable', 'integer', 'exists:users,id'],
            'type' => ['required', Rule::in(['free', 'paid'])],
            'price' => ['nullable', 'integer', 'min:0', 'max:1000000000'],
            'delivery_type' => ['required', Rule::in(['spotplayer', 'skyroom', 'onsite'])],
            'status' => ['required', Rule::in(['draft', 'published', 'archived'])],
        ]);

        if (! empty($data['teacher_id'])) {
            abort_unless(User::findOrFail($data['teacher_id'])->hasRole('teacher'), 422, 'کاربر انتخاب‌شده معلم نیست.');
        }

        $data['slug'] = Str::slug($data['title']).'-'.Str::lower(Str::random(6));
        $data['price'] = $data['type'] === 'paid' ? (int) ($data['price'] ?? 0) : 0;

        Course::create($data);

        return back()->with('success', 'دوره با موفقیت ایجاد شد.');
    }

    public function update(Request $request, Course $course): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:10000'],
            'subject' => ['nullable', 'string', 'max:100'],
            'grade' => ['nullable', 'string', 'max:50'],
            'teacher_id' => ['nullable', 'integer', 'exists:users,id'],
            'type' => ['required', Rule::in(['free', 'paid'])],
            'price' => ['nullable', 'integer', 'min:0', 'max:1000000000'],
            'delivery_type' => ['required', Rule::in(['spotplayer', 'skyroom', 'onsite'])],
            'status' => ['required', Rule::in(['draft', 'published', 'archived'])],
        ]);

        if (! empty($data['teacher_id'])) {
            abort_unless(User::findOrFail($data['teacher_id'])->hasRole('teacher'), 422, 'کاربر انتخاب‌شده معلم نیست.');
        }

        $data['price'] = $data['type'] === 'paid' ? (int) ($data['price'] ?? 0) : 0;
        $course->update($data);

        return back()->with('success', 'دوره به‌روزرسانی شد.');
    }

    public function destroy(Course $course): RedirectResponse
    {
        abort_if($course->classrooms()->exists(), 422, 'ابتدا کلاس‌های وابسته به این دوره را منتقل یا حذف کنید.');

        $course->delete();

        return back()->with('success', 'دوره حذف شد.');
    }
}
