<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassSession;
use App\Models\Classroom;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class OnlineClassController extends Controller
{
    public function index(Request $request): View
    {
        $query = ClassSession::where('session_type', 'online')
            ->with(['classroom.course:id,title', 'classroom.teacher:id,name'])
            ->withCount('attendances')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')->toString()))
            ->when($request->filled('q'), function ($q) use ($request): void {
                $term = trim($request->string('q')->toString());
                $q->where(fn ($search) => $search->where('title', 'like', "%{$term}%")
                    ->orWhereHas('classroom', fn ($classes) => $classes->where('name', 'like', "%{$term}%")));
            });

        $sessions = $query->orderByDesc('starts_at')->paginate(20)->withQueryString();
        $stats = [
            'today' => ClassSession::where('session_type', 'online')->whereDate('starts_at', today())->count(),
            'week' => ClassSession::where('session_type', 'online')->whereBetween('starts_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'upcoming' => ClassSession::where('session_type', 'online')->where('status', 'scheduled')->where('starts_at', '>=', now())->count(),
            'waiting' => Classroom::whereHas('sessions', fn ($q) => $q->where('session_type', 'online')->whereNull('meeting_url'))->whereNull('skyroom_room_id')->count(),
        ];

        return view('admin.online-classes.index', compact('sessions', 'stats'));
    }
}
