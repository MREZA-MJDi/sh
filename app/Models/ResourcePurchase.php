<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ResourcePurchase extends Model
{
    protected $fillable = [
        'learning_resource_id', 'student_id', 'amount', 'reference', 'method',
        'provider', 'provider_reference', 'status', 'note', 'verified_by',
        'verified_at', 'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'verified_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function resource(): BelongsTo
    {
        return $this->belongsTo(LearningResource::class, 'learning_resource_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
