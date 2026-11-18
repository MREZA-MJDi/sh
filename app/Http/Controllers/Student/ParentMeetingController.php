<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ParentMeeting;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class ParentMeetingController extends Controller
{
    public function index(Request $request): View
    {
        $meetings = ParentMeeting::query()
            ->where('student_id', $request->user()->id)
            ->with(['guardian:id,name', 'classroom:id,name', 'course:id,title', 'creator:id,name', 'responder:id,name'])
            ->orderByRaw("CASE WHEN status IN ('requested', 'proposed') THEN 0 WHEN status = 'scheduled' THEN 1 ELSE 2 END")
            ->orderByRaw('COALESCE(starts_at, preferred_at) ASC')
            ->paginate(15)
            ->withQueryString();

        return view('student.meetings', compact('meetings'));
    }
}
