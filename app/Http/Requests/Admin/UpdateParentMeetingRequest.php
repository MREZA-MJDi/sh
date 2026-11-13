<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class UpdateParentMeetingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermissionTo('meetings.manage') ?? false;
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($this->input('status') === 'requested' && $this->filled('scheduled_at')) {
                $validator->errors()->add('scheduled_at', 'برای باقی‌ماندن درخواست در وضعیت ثبت‌شده، زمان پیشنهادی را پاک کنید.');
            }
            if ($this->input('status') === 'proposed' && ! $this->filled('scheduled_at')) {
                $validator->errors()->add('scheduled_at', 'برای پیشنهاد زمان جلسه، زمان شروع الزامی است.');
            }
            $scheduledTimestamp = $this->filled('scheduled_at') ? strtotime((string) $this->input('scheduled_at')) : false;
            if (in_array($this->input('status'), ['requested', 'proposed'], true)
                && $scheduledTimestamp !== false
                && $scheduledTimestamp < now()->timestamp) {
                $validator->errors()->add('scheduled_at', 'زمان پیشنهادی باید در آینده باشد.');
            }
            if ($this->filled('ends_at') && ! $this->filled('scheduled_at')) {
                $validator->errors()->add('scheduled_at', 'برای ثبت زمان پایان، ابتدا زمان شروع را مشخص کنید.');
            }
        }];
    }

    public function rules(): array
    {
        return [
            'scheduled_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after:scheduled_at'],
            'status' => ['required', Rule::in(['requested', 'proposed', 'accepted', 'declined', 'completed', 'cancelled'])],
            'admin_notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}