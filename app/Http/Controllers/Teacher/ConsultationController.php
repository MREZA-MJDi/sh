<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Consultation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class ConsultationController extends Controller
{
    public function index(Request $request): View
    {
        $consultations = Consultation::query()
            ->where('requester_id', $request->user()->id)
            ->with(['student:id,name', 'replies.sender:id,name'])
            ->latest()
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

        return redirect()
            ->route('teacher.consultations.index')
            ->with('success', 'درخواست مشاوره برای بررسی در کارتابل مدیریت ثبت شد. کد پیگیری: '.$consultation->id);
    }
}
