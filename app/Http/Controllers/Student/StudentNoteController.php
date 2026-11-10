<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\SaveStudentNoteRequest;
use App\Models\StudentNote;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class StudentNoteController extends Controller
{
    public function store(SaveStudentNoteRequest $request): RedirectResponse
    {
        $request->user()->studentNotes()->create([
            'body' => trim($request->validated('body')),
            'tone' => $request->validated('tone', ''),
        ]);

        return back()->with('status', 'یادداشت با موفقیت ثبت شد.');
    }

    public function update(SaveStudentNoteRequest $request, StudentNote $note): RedirectResponse
    {
        abort_unless($note->student_id === $request->user()->id, 404);

        $note->update([
            'body' => trim($request->validated('body')),
            'tone' => $request->validated('tone', ''),
        ]);

        return back()->with('status', 'یادداشت به‌روزرسانی شد.');
    }

    public function destroy(Request $request, StudentNote $note): RedirectResponse
    {
        abort_unless($note->student_id === $request->user()->id, 404);

        $note->delete();

        return back()->with('status', 'یادداشت حذف شد.');
    }
}
