<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateConsultationRequest;
use App\Models\Consultation;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ConsultationController extends Controller
{
    public function index(): View
    {
        $consultations = Consultation::with(['requester', 'student', 'teacher'])->latest()->paginate(20);

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
}
