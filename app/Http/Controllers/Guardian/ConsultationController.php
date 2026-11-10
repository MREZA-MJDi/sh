<?php

namespace App\Http\Controllers\Guardian;

use App\Http\Controllers\Controller;
use App\Http\Requests\Guardian\ReplyConsultationRequest;
use App\Http\Requests\Guardian\StoreConsultationRequest;
use App\Models\Consultation;
use App\Models\ConsultationReply;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class ConsultationController extends Controller
{
    public function index(Request $request): View
    {
        $children = $this->linkedChildren($request);
        $selected_child = $children->firstWhere('id', $request->integer('child')) ?? $children->first();

        $consultations = Consultation::query()
            ->where('requester_id', $request->user()->id)
            ->when($selected_child, fn ($query) => $query->where('student_id', $selected_child->id))
            ->with(['student:id,name,grade', 'replies.sender:id,name'])
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('parent.consult.index', compact('consultations', 'children', 'selected_child'));
    }

    public function create(Request $request): View
    {
        $children = $this->linkedChildren($request);

        abort_if($children->isEmpty(), 403, 'برای ثبت درخواست، ابتدا باید دانش‌آموزی به حساب شما متصل شود.');

        $selectedChild = $children->firstWhere('id', $request->integer('child')) ?? $children->first();
        $selected_child = $selectedChild;

        return view('parent.consult.create', compact('children', 'selectedChild', 'selected_child'));
    }

    public function store(StoreConsultationRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $consultation = Consultation::query()->create([
            'requester_id' => $request->user()->id,
            'student_id' => $data['student_id'],
            'type' => $data['type'],
            'topic' => $data['topic'],
            'contact_phone' => $data['contact_phone'] ?? $request->user()->phone,
            'desired_at' => $data['desired_at'] ?? null,
            'notes' => $data['body'],
            'status' => 'pending',
        ]);

        return redirect()
            ->route('guardian.consultations.show', $consultation)
            ->with('status', 'درخواست مشاوره ثبت شد.');
    }

    public function show(Request $request, Consultation $consultation): View
    {
        $consultation = $request->user()->consultations()
            ->whereKey($consultation->getKey())
            ->with(['student:id,name,grade', 'replies.sender:id,name'])
            ->firstOrFail();

        $children = $this->linkedChildren($request);
        $selected_child = $children->firstWhere('id', $consultation->student_id) ?? $children->first();

        return view('parent.consult.show', compact('consultation', 'children', 'selected_child'));
    }

    public function reply(
        ReplyConsultationRequest $request,
        Consultation $consultation
    ): RedirectResponse {
        $consultation = $request->user()->consultations()
            ->whereKey($consultation->getKey())
            ->firstOrFail();

        abort_if(in_array($consultation->status, ['done', 'cancelled'], true), 422, 'این درخواست بسته شده است.');

        ConsultationReply::query()->create([
            'consultation_id' => $consultation->id,
            'sender_id' => $request->user()->id,
            'body' => $request->validated('body'),
        ]);

        return back()->with('status', 'پیام شما به درخواست مشاوره اضافه شد.');
    }

    public function destroy(Request $request, Consultation $consultation): RedirectResponse
    {
        $consultation = $request->user()->consultations()
            ->whereKey($consultation->getKey())
            ->firstOrFail();

        abort_unless($consultation->status === 'pending', 422, 'درخواست هماهنگ‌شده یا بررسی‌شده قابل حذف نیست.');

        $consultation->delete();

        return redirect()->route('guardian.consultations.index')->with('status', 'درخواست مشاوره حذف شد.');
    }

    private function linkedChildren(Request $request)
    {
        return $request->user()->children()
            ->where('users.is_active', true)
            ->whereHas('roles', fn ($roles) => $roles->where('name', 'student'))
            ->orderBy('users.name')
            ->get(['users.id', 'users.name', 'users.grade']);
    }
}
