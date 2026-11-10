<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'phone', 'password', 'is_active', 'grade', 'subject',
        'profile_note', 'external_student_id',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function setPhoneAttribute(?string $value): void
    {
        if ($value === null || trim($value) === '') {
            $this->attributes['phone'] = null;

            return;
        }

        $digits = strtr(trim($value), [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);

        $this->attributes['phone'] = preg_replace('/[\\s-]+/u', '', $digits) ?? $digits;
    }

    public function setExternalStudentIdAttribute(?string $value): void
    {
        if ($value === null || trim($value) === '') {
            $this->attributes['external_student_id'] = null;

            return;
        }

        $identifier = strtr(trim($value), [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);

        $this->attributes['external_student_id'] = preg_replace('/\\s+/u', '', $identifier) ?? $identifier;
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)->withTimestamps();
    }

    public function hasRole(string|array $roles): bool
    {
        $names = array_map('strval', (array) $roles);

        if ($this->relationLoaded('roles')) {
            return $this->getRelation('roles')->contains(
                fn (Role $role): bool => in_array($role->name, $names, true)
            );
        }

        return $this->roles()->whereIn('name', $names)->exists();
    }

    public function hasPermissionTo(string $permission): bool
    {
        $this->loadMissing('roles.permissions');

        return $this->getRelation('roles')->contains(
            fn (Role $role): bool => $role->name === 'admin'
                || $role->permissions->contains('name', $permission)
        );
    }

    public function taughtClasses(): HasMany
    {
        return $this->hasMany(Classroom::class, 'teacher_id');
    }

    public function enrolledClasses(): BelongsToMany
    {
        return $this->belongsToMany(Classroom::class, 'classroom_student', 'student_id', 'classroom_id')
            ->withPivot('assigned_by')->withTimestamps();
    }

    public function children(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'guardian_student', 'guardian_id', 'student_id')
            ->withTimestamps();
    }

    public function guardians(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'guardian_student', 'student_id', 'guardian_id')
            ->withTimestamps();
    }

    public function studentNotes(): HasMany
    {
        return $this->hasMany(StudentNote::class, 'student_id');
    }

    public function consultations(): HasMany
    {
        return $this->hasMany(Consultation::class, 'requester_id');
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(Attendance::class, 'student_id');
    }

    public function homeworkSubmissions(): HasMany
    {
        return $this->hasMany(HomeworkSubmission::class, 'student_id');
    }

    public function courseEnrollments(): HasMany
    {
        return $this->hasMany(CourseEnrollment::class, 'student_id');
    }

}
