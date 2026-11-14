<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VideoUploadSession extends Model
{
    protected $fillable = ['upload_id', 'user_id', 'target', 'target_id', 'status', 'bytes_uploaded', 'metadata', 'expires_at', 'completed_at'];

    protected function casts(): array
    {
        return ['bytes_uploaded' => 'integer', 'metadata' => 'array', 'expires_at' => 'datetime', 'completed_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
