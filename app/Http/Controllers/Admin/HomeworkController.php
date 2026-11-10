<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\Homework;
use App\Models\HomeworkSubmission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class HomeworkController extends Controller
{
    public function index(): View
    {
        $homeworks = Homework::with('classroom')
            ->withCount(['submissions', 'submissions as pending_submissions_count' => fn ($query) => $query->where('status', 'submitted')])
            ->latest()->paginate(12);

        $classrooms = Classroom::where('status', 'active')->orderBy('name')->get(['id', 'name']);

        $submissions = HomeworkSubmission::with(['homework.classroom', 'student'])
            ->latest('submitted_at')->limit(30)->get();

        return view('admin.homework.index', compact('homeworks', 'classrooms', 'submissions'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'classroom_id' => ['required', 'integer', 'exists:classrooms,id'],
            'title' => ['required', 'string', 'max:180'],
            'instructions' => ['nullable', 'string', 'max:20000'],
            'due_at' => ['nullable', 'date'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png,zip', 'max:20480'],
            'status' => ['required', Rule::in(['draft', 'published'])],
        ]);

        $path = isset($data['attachment'])
            ? $data['attachment']->store('private/homework', 'local')
            : null;

        Homework::create([
            'classroom_id' => $data['classroom_id'],
            'title' => $data['title'],
            'instructions' => $data['instructions'] ?? null,
            'due_at' => $data['due_at'] ?? null,
            'attachment_path' => $path,
            'status' => $data['status'],
            'created_by' => auth()->id(),
        ]);

        return back()->with('success', 'تکلیف با موفقیت ثبت شد.');
    }

    public function review(Request $request, HomeworkSubmission $submission): RedirectResponse
    {
        $data = $request->validate([
            'score' => ['nullable', 'numeric', 'min:0', 'max:20'],
            'feedback' => ['required', 'string', 'max:10000'],
        ]);

        $submission->update([
            'score' => $data['score'] ?? null,
            'feedback' => $data['feedback'],
            'status' => 'reviewed',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        return back()->with('success', 'نتیجه بررسی تکلیف ذخیره شد.');
    }

    public function downloadAttachment(Homework $homework)
    {
        abort_unless($homework->attachment_path && Storage::disk('local')->exists($homework->attachment_path), 404);

        return Storage::disk('local')->download($homework->attachment_path);
    }
}
