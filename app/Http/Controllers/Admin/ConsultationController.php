<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateConsultationRequest;
use App\Models\Consultation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ConsultationController extends Controller
{
    public function index(Request $request): View
    {
        $selectedId = $request->integer('consultation');
        $consultations = Consultation::with(['requester', 'student', 'teacher'])
            ->when($selectedId > 0, fn ($query) => $query->whereKey($selectedId))
            ->latest()->paginate(20)->withQueryString();

        return view('admin.consultations.index', compact('consultations'));
    }

    public function update(UpdateConsultationRequest $request, Consultation $consultation): RedirectResponse
    {
        $data = $request->validated();
        $nextStatus = match ($data['status']) {
            'approved' => 'scheduled',
            'completed' => 'done',
            default => $data['status'],
        };
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
        $consultation->update($updates);

        return back()->with('success', 'وضعیت درخواست مشاوره به‌روزرسانی شد.');
    }
    public function destroy(\Illuminate\Http\Request $request, Consultation $consultation): RedirectResponse
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        $consultation->delete();

        return back()->with('success', 'درخواست مشاوره حذف شد.');
    }
}
