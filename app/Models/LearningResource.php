<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LearningResource extends Model
{
    use \App\Models\Concerns\SanitizesRichText;

    protected $fillable = [
        'classroom_id',
        'course_id',
        'uploaded_by',
        'title',
        'description',
        'kind',
        'price',
        'is_free',
        'visibility',
        'release_at',
        'downloadable',
        'sort_order',
        'file_path',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'is_free' => 'boolean',
            'release_at' => 'datetime',
            'downloadable' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function accessLogs(): HasMany
    {
        return $this->hasMany(LearningResourceAccessLog::class, 'learning_resource_id');
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(ResourcePurchase::class, 'learning_resource_id');
    }
}