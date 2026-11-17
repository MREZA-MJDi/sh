<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Payment;
use App\Models\User;
use App\Services\Courses\CourseEnrollmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class PaymentController extends Controller
{
    public function index(Request $request): View
    {
        $payments = Payment::with(['student:id,name,email,phone', 'course:id,title,price', 'verifier:id,name'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')->toString()))
            ->when($request->filled('q'), function ($q) use ($request): void {
                $term = trim($request->string('q')->toString());
                $q->where(fn ($search) => $search->where('reference', 'like', "%{$term}%")
                    ->orWhere('provider_reference', 'like', "%{$term}%")
                    ->orWhereHas('student', fn ($users) => $users->where('name', 'like', "%{$term}%"))
                    ->orWhereHas('course', fn ($courses) => $courses->where('title', 'like', "%{$term}%")));
            })->latest()->paginate(20)->withQueryString();

        $stats = [
            'today' => Payment::where('status', 'paid')->whereDate('paid_at', today())->sum('amount'),
            'month' => Payment::where('status', 'paid')->whereBetween('paid_at', [now()->startOfMonth(), now()->endOfMonth()])->sum('amount'),
            'review' => Payment::where('status', 'pending_review')->count(),
            'provider' => Payment::where('status', 'awaiting_provider')->count(),
        ];
        $students = User::where('is_active', true)->whereHas('roles', fn ($q) => $q->where('name', 'student'))->orderBy('name')->get(['id', 'name']);
        $courses = Course::where('status', 'published')->where('type', 'paid')->orderBy('title')->get(['id', 'title', 'price']);

        return view('admin.payments.index', compact('payments', 'stats', 'students', 'courses'));
    }

    public function storeManual(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'student_id' => ['required', 'integer', 'exists:users,id'],
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'amount' => ['required', 'integer', 'min:1'],
            'method' => ['required', 'in:cash,bank_transfer'],
            'provider_reference' => ['nullable', 'string', 'max:190'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);
        $student = User::whereKey($data['student_id'])->where('is_active', true)
            ->whereHas('roles', fn ($q) => $q->where('name', 'student'))->firstOrFail();
        $course = Course::whereKey($data['course_id'])->where('status', 'published')->where('type', 'paid')->firstOrFail();

        if ((int) $data['amount'] < (int) $course->price) {
            throw ValidationException::withMessages(['amount' => 'مبلغ ثبت‌شده از شهریه دوره کمتر است.']);
        }

        Payment::create([
            'reference' => 'SHM-'.now()->format('ymd').'-'.Str::upper(Str::random(8)),
            'student_id' => $student->id,
            'course_id' => $course->id,
            'amount' => $data['amount'],
            'method' => $data['method'],
            'provider' => 'manual',
            'status' => 'pending_review',
            'provider_reference' => $data['provider_reference'] ?? null,
            'note' => $data['note'] ?? null,
        ]);

        return back()->with('success', 'رسید پرداخت دستی ثبت شد؛ تا زمان تأیید مدیر هیچ مجوزی فعال نمی‌شود.');
    }

    public function verifyManual(Payment $payment, Request $request, CourseEnrollmentService $enrollments): RedirectResponse
    {
        DB::transaction(function () use ($payment, $request, $enrollments): void {
            $record = Payment::with(['student', 'course'])->lockForUpdate()->findOrFail($payment->id);
            if ($record->status === 'paid') return;
            if ($record->provider !== 'manual' || $record->status !== 'pending_review') {
                throw ValidationException::withMessages(['payment' => 'فقط پرداخت دستی در انتظار بررسی قابل تأیید است.']);
            }

            $enrollments->grantAfterVerifiedPayment($record->student, $record->course, 'payment:'.$record->reference);
            $record->update(['status' => 'paid', 'verified_by' => $request->user()->id, 'verified_at' => now(), 'paid_at' => now()]);
        }, 3);

        return back()->with('success', 'پرداخت دستی تأیید شد و مجوز دوره فعال گردید.');
    }
}
