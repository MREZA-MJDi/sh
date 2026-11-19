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
        $data['status'] = match ($data['status']) {
            'approved' => 'scheduled',
            'completed' => 'done',
            default => $data['status'],
        };

        $consultation->update($data + ['handled_by' => auth()->id()]);

        return back()->with('success', 'وضعیت درخواست مشاوره به‌روزرسانی شد.');
    }
    public function destroy(\Illuminate\Http\Request $request, Consultation $consultation): RedirectResponse
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        $consultation->delete();

        return back()->with('success', 'درخواست مشاوره حذف شد.');
    }
}
