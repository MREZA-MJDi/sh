<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\StoreStudentNoteRequest;
use App\Http\Requests\Student\UpdateStudentNoteRequest;
use App\Models\StudentNote;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class NoteController extends Controller
{
    public function index(Request $request): View
    {
        $notes = $request->user()
            ->studentNotes()
            ->latest()
            ->paginate(12);

        return view('student.notes.index', compact('notes'));
    }

    public function store(StoreStudentNoteRequest $request): RedirectResponse
    {
        $request->user()->studentNotes()->create([
            'body' => $request->validated('text'),
            'tone' => $request->validated('tone') ?? '',
        ]);

        return back()->with('status', 'یادداشت با موفقیت ذخیره شد.');
    }

    public function update(
        UpdateStudentNoteRequest $request,
        StudentNote $note
    ): RedirectResponse {
        $note = $request->user()->studentNotes()->whereKey($note->getKey())->firstOrFail();

        $note->update([
            'body' => $request->validated('text'),
            'tone' => $request->validated('tone') ?? '',
        ]);

        return back()->with('status', 'یادداشت به‌روزرسانی شد.');
    }

    public function destroy(Request $request, StudentNote $note): RedirectResponse
    {
        $request->user()->studentNotes()->whereKey($note->getKey())->firstOrFail()->delete();

        return back()->with('status', 'یادداشت حذف شد.');
    }
}
