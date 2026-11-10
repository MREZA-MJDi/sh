<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpotPlayerLicense extends Model
{
    protected $fillable = [
        'student_id',
        'course_id',
        'external_license_id',
        'license_key',
        'download_url',
        'max_devices',
        'active_devices',
        'status',
        'issued_at',
        'revoked_at',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'license_key' => 'encrypted',
            'download_url' => 'encrypted',
            'issued_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
