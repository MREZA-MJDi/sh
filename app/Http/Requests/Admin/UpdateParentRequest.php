<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateParentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermissionTo('users.manage') ?? false;
    }

    public function rules(): array
    {
        $parent = $this->route('parent');
        return [
            'name' => ['required', 'string', 'max:160'],
            'email' => ['required', 'email:rfc', 'max:190', Rule::unique('users', 'email')->ignore($parent->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['nullable', 'string', 'min:12', 'confirmed'],
            'child_ids' => ['nullable', 'array', 'max:30'],
            'child_ids.*' => ['integer', 'distinct', 'exists:users,id'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $parent = $this->route('parent');
            if (! $parent->hasRole('parent') || $parent->hasRole('admin')) {
                $validator->errors()->add('parent', 'حساب انتخاب‌شده ولی قابل ویرایش نیست.');
            }
            $ids = $this->input('child_ids', []);
            if ($ids !== []) {
                $valid = User::query()->whereIn('id', $ids)->where('is_active', true)
                    ->whereHas('roles', fn ($query) => $query->where('name', 'student'))->count();
                if ($valid !== count($ids)) {
                    $validator->errors()->add('child_ids', 'فقط حساب‌های فعال دارای نقش دانش‌آموز قابل اتصال هستند.');
                }
            }
        }];
    }
}
