<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IntegrationRun extends Model
{
    protected $fillable = [
        'provider',
        'action',
        'entity_type',
        'entity_id',
        'status',
        'idempotency_key',
        'attempts',
        'available_at',
        'correlation_id',
        'request_meta',
        'response_meta',
        'error_message',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'attempts' => 'integer',
            'available_at' => 'datetime',
            'request_meta' => 'array',
            'response_meta' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }
}
