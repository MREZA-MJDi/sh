<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseLicense extends Model
{
    public const QUEUED = 'queued';
    public const WAITING_CONTRACT = 'waiting_contract';
    public const ACTIVE = 'active';
    public const FAILED = 'failed';
    public const REVOKED = 'revoked';

    // وضعیت فقط توسط Job/وب‌هوک سرویس تغییر می‌کند، نه فرم ادمین.
    protected $fillable = ['enrollment_id', 'status', 'external_license_id', 'license_key', 'attempts', 'last_error', 'issued_at'];

    protected $casts = [
        'license_key' => 'encrypted',
        'issued_at' => 'datetime',
    ];

    protected $hidden = ['license_key'];

    public function enrollment(): BelongsTo { return $this->belongsTo(Enrollment::class); }
}
