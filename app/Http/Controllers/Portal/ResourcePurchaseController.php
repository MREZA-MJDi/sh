<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\LearningResource;
use App\Models\ResourcePurchase;
use App\Services\LearningResourceAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class ResourcePurchaseController extends Controller
{
    public function store(
        Request $request,
        LearningResource $resource,
        LearningResourceAccessService $access
    ): RedirectResponse {
        $student = $access->resolveStudent($request->user(), $request->integer('child'));
        $access->authorizePurchase($student, $resource);

        $data = $request->validate([
            'provider_reference' => ['required', 'string', 'max:190'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $purchase = DB::transaction(function () use ($resource, $student, $data): ResourcePurchase {
            $purchase = ResourcePurchase::query()
                ->where('learning_resource_id', $resource->id)
                ->where('student_id', $student->id)
                ->lockForUpdate()
                ->first();

            if ($purchase?->status === 'paid') {
                return $purchase;
            }

            $purchase ??= new ResourcePurchase([
                'learning_resource_id' => $resource->id,
                'student_id' => $student->id,
                'reference' => 'SHR-'.now()->format('ymd').'-'.Str::upper(Str::random(8)),
            ]);

            $purchase->fill([
                'amount' => (int) $resource->price,
                'method' => 'bank_transfer',
                'provider' => 'manual',
                'provider_reference' => trim($data['provider_reference']),
                'note' => filled($data['note'] ?? null) ? trim($data['note']) : null,
                'status' => 'pending_review',
                'verified_by' => null,
                'verified_at' => null,
                'paid_at' => null,
            ])->save();

            return $purchase;
        }, 3);

        if ($purchase->status === 'paid') {
            return back()->with('status', 'این محصول قبلاً برای این دانش‌آموز تأیید شده است.');
        }

        return back()->with('status', 'درخواست خرید ثبت شد. دسترسی پس از بررسی و تأیید رسید توسط آموزشگاه فعال می‌شود.');
    }
}
