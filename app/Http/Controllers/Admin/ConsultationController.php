<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Consultation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ConsultationController extends Controller
{
    public function index(): View
    {
        $consultations = Consultation::with(['requester', 'student', 'teacher'])
            ->latest()->paginate(20);

        return view('admin.consultations.index', compact('consultations'));
    }

    public function update(Request $request, Consultation $consultation): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['pending', 'approved', 'completed', 'cancelled'])],
            'scheduled_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $consultation->update($data + ['handled_by' => auth()->id()]);

        return back()->with('success', 'وضعیت درخواست مشاوره به‌روزرسانی شد.');
    }
}
