<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Consultation;
use App\Models\User;
use App\Services\Admin\ClassroomWorkspaceService;
use App\Notifications\ConsultationRequestNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class ConsultationController extends Controller
{
    public function index(Request $request, ClassroomWorkspaceService $workspace): View
    {
        $teacher = $request->user();
        $assignedStudentIds = DB::table('classroom_student')
            ->select('student_id')
            ->whereIn('classroom_id', $workspace->queryFor($teacher)->select('classrooms.id'));

        $consultations = Consultation::query()
            ->where(function ($query) use ($teacher, $assignedStudentIds): void {
                $query->where('requester_id', $teacher->id)
                    ->orWhereIn('student_id', clone $assignedStudentIds);
            })
            ->with(['requester:id,name', 'student:id,name', 'teacher:id,name'])
            ->latest()
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('teacher.consultations.index', compact('consultations'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:ticket,phone,in_person'],
            'topic' => ['required', 'string', 'min:3', 'max:190'],
            'contact_phone' => ['nullable', 'string', 'max:30'],
            'desired_at' => ['nullable', 'date', 'after:now'],
            'body' => ['required', 'string', 'min:5', 'max:5000'],
        ]);

        $consultation = Consultation::query()->create([
            'requester_id' => $request->user()->id,
            'type' => $data['type'],
            'topic' => trim($data['topic']),
            'contact_phone' => $data['contact_phone'] ?? $request->user()->phone,
            'desired_at' => $data['desired_at'] ?? null,
            'notes' => trim($data['body']),
            'status' => 'pending',
        ]);

        User::query()
            ->where('is_active', true)
            ->whereHas('roles', fn (Builder $roles) => $roles->where('name', 'admin'))
            ->orderBy('id')
            ->chunkById(100, fn ($admins) => $admins->each(
                fn (User $admin) => $admin->notify(new ConsultationRequestNotification($consultation))
            ));

        return redirect()
            ->route('teacher.consultations.index')
            ->with('success', 'درخواست مشاوره برای بررسی در کارتابل مدیریت ثبت شد. کد پیگیری: '.$consultation->id);
    }
}
