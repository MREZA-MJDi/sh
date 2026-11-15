<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Enrollment extends Model
{
    public const PENDING = 'pending_payment';
    public const ACTIVE = 'active';
    public const CANCELLED = 'cancelled';
    public const REFUNDED = 'refunded';

    protected $fillable = ['user_id', 'course_id', 'status', 'source', 'amount', 'payment_ref', 'paid_at'];

    protected $casts = ['paid_at' => 'datetime'];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }

    public function course(): BelongsTo { return $this->belongsTo(Course::class); }

    public function license(): HasOne { return $this->hasOne(CourseLicense::class); }

    public function isActive(): bool { return $this->status === self::ACTIVE; }
}
