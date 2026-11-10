<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateConsultationRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->hasPermissionTo('consultations.manage') ?? false; }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(['pending', 'approved', 'completed', 'cancelled'])],
            'scheduled_at' => ['nullable', 'date', 'after_or_equal:now'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
