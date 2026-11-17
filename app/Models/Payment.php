<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Payment extends Model
{
    protected $fillable = ['reference', 'student_id', 'course_id', 'amount', 'method', 'provider', 'status', 'provider_reference', 'note', 'metadata', 'verified_by', 'verified_at', 'paid_at'];

    protected function casts(): array
    {
        return ['amount' => 'integer', 'metadata' => 'array', 'verified_at' => 'datetime', 'paid_at' => 'datetime'];
    }

    public function student(): BelongsTo { return $this->belongsTo(User::class, 'student_id'); }
    public function course(): BelongsTo { return $this->belongsTo(Course::class); }
    public function verifier(): BelongsTo { return $this->belongsTo(User::class, 'verified_by'); }
}
