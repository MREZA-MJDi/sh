<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateConsultationRequest;
use App\Models\Consultation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ConsultationController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'in:pending,scheduled,done,cancelled'],
        ]);
        $selectedId = $request->integer('consultation');
        $term = trim((string) ($filters['q'] ?? ''));

        $baseQuery = Consultation::query()
            ->when($term !== '', fn ($query) => $query->where(function ($search) use ($term): void {
                $search->where('topic', 'like', "%{$term}%")
                    ->orWhereHas('requester', fn ($requester) => $requester->where('name', 'like', "%{$term}%"))
                    ->orWhereHas('student', fn ($student) => $student->where('name', 'like', "%{$term}%"))
                    ->orWhereHas('teacher', fn ($teacher) => $teacher->where('name', 'like', "%{$term}%"));
            }));

        $counts = [
            'total' => (clone $baseQuery)->count(),
            'pending' => (clone $baseQuery)->where('status', 'pending')->count(),
            'scheduled' => (clone $baseQuery)->where('status', 'scheduled')->count(),
            'done' => (clone $baseQuery)->where('status', 'done')->count(),
        ];

        $consultations = (clone $baseQuery)
            ->when($selectedId > 0, fn ($query) => $query->whereKey($selectedId))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->with(['requester', 'student', 'teacher'])
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.consultations.index', compact('consultations', 'counts', 'filters'));
    }

    public function update(UpdateConsultationRequest $request, Consultation $consultation): RedirectResponse
    {
        $data = $request->validated();
        $nextStatus = match ($data['status']) {
            'approved' => 'scheduled',
            'completed' => 'done',
            default => $data['status'],
        };
        DB::transaction(function () use ($consultation, $data, $nextStatus): void {
            $consultation = Consultation::query()->lockForUpdate()->findOrFail($consultation->id);
            $currentStatus = match ($consultation->status) {
                'approved' => 'scheduled',
                'completed' => 'done',
                default => $consultation->status,
            };

            $allowedTransitions = match ($currentStatus) {
                'pending' => ['pending', 'scheduled', 'cancelled'],
                'scheduled' => ['scheduled', 'done', 'cancelled'],
                'done' => ['done'],
                'cancelled' => ['cancelled'],
                default => [],
            };

            abort_unless(in_array($nextStatus, $allowedTransitions, true), 422, 'وضعیت این درخواست قابل تغییر نیست.');

            $updates = [
                'status' => $nextStatus,
                'handled_by' => auth()->id(),
            ];
            if (array_key_exists('scheduled_at', $data)) {
                $updates['scheduled_at'] = $data['scheduled_at'];
            }
            if (array_key_exists('notes', $data)) {
                $updates['notes'] = $data['notes'];
            }
            if ($nextStatus === 'scheduled' && blank(array_key_exists('scheduled_at', $updates) ? $updates['scheduled_at'] : $consultation->scheduled_at)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'scheduled_at' => 'زمان مشاوره باید مشخص باشد.',
                ]);
            }

            $consultation->update($updates);
        }, 3);

        return back()->with('success', 'وضعیت درخواست مشاوره به‌روزرسانی شد.');
    }

    public function destroy(Request $request, Consultation $consultation): RedirectResponse
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        $consultation->delete();

        return back()->with('success', 'درخواست مشاوره حذف شد.');
    }
}
