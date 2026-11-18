<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class AcademicGrade extends Model
{
    protected $fillable = ['code', 'name', 'level', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return [
            'level' => 'integer',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class, 'academic_grade_id');
    }

    public function students(): HasMany
    {
        return $this->hasMany(User::class, 'academic_grade_id');
    }

    public static function resolveIdFromLegacyName(?string $name): ?int
    {
        $value = trim((string) $name);

        if ($value === '' || in_array($value, ['عمومی', 'سایر', 'بدون پایه'], true)) {
            return null;
        }

        $value = preg_replace('/^پایه\s+/u', '', $value) ?? $value;
        $value = strtr($value, [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);

        return static::query()
            ->where(fn ($query) => $query->where('name', $value)->orWhere('code', $value))
            ->value('id');
    }
}
