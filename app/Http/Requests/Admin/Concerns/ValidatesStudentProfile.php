<?php

namespace App\Http\Requests\Admin\Concerns;

use App\Models\User;
use Illuminate\Validation\Rule;

trait ValidatesStudentProfile
{
    protected function normalizeStudentProfile(): void
    {
        foreach (['national_id', 'postal_code'] as $field) {
            $value = $this->input($field);
            if (is_string($value)) {
                $this->merge([$field => strtr($value, [
                    '۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9',
                    '٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4','٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9',
                ])]);
            }
        }

        $first = $this->input('first_name');
        $last = $this->input('last_name');
        if (is_string($first) && is_string($last) && $first !== '' && $last !== '') {
            $this->merge(['name' => $first.' '.$last]);
        }
    }

    protected function studentProfileRules(?User $student = null): array
    {
        $required = $student ? ['sometimes', 'required'] : ['required'];
        $nationalId = Rule::unique('users', 'national_id');
        if ($student) {
            $nationalId->ignore($student);
        }

        return [
            'first_name' => [...$required, 'string', 'max:75'],
            'last_name' => [...$required, 'string', 'max:75'],
            'national_id' => [...$required, 'string', 'regex:/^[0-9]{10}$/D', $nationalId],
            'father_name' => [...$required, 'string', 'max:100'],
            'birth_date' => [...$required, 'date_format:Y-m-d', 'before_or_equal:today'],
            'school_name' => [...$required, 'string', 'max:180'],
            'province' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:500'],
            'postal_code' => ['nullable', 'string', 'regex:/^[0-9]{10}$/D'],
        ];
    }

    public function attributes(): array
    {
        return [
            'first_name' => 'نام', 'last_name' => 'نام خانوادگی', 'national_id' => 'کد ملی',
            'father_name' => 'نام پدر', 'birth_date' => 'تاریخ تولد', 'school_name' => 'نام مدرسه',
            'province' => 'استان', 'city' => 'شهر', 'address' => 'نشانی', 'postal_code' => 'کد پستی',
        ];
    }
}
