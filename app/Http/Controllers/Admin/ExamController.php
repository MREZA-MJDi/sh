<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\Exam;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ExamController extends Controller
{
    public function index(): View
    {
        $exams = Exam::with(['classroom', 'course'])->withCount('results')->latest()->paginate(15);
        $classrooms = Classroom::orderBy('name')->get(['id', 'name']);
        $courses = Course::orderBy('title')->get(['id', 'title']);

        return view('admin.exams.index', compact('exams', 'classrooms', 'courses'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:10000'],
            'classroom_id' => ['nullable', 'integer', 'exists:classrooms,id'],
            'course_id' => ['nullable', 'integer', 'exists:courses,id'],
            'provider' => ['required', Rule::in(['internal', 'quiz24'])],
            'external_id' => ['nullable', 'string', 'max:190'],
            'starts_at' => ['nullable', 'date'],
            'status' => ['required', Rule::in(['draft', 'scheduled', 'published', 'closed'])],
        ]);

        if ($data['provider'] === 'quiz24' && empty($data['external_id'])) {
            return back()->withErrors(['external_id' => 'برای آزمون خارجی، شناسه آزمون الزامی است.'])->withInput();
        }

        Exam::create($data + ['created_by' => auth()->id()]);

        return back()->with('success', 'آزمون ثبت شد.');
    }
}
