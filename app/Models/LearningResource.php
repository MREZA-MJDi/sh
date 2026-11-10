<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LearningResource extends Model
{
    use \App\Models\Concerns\SanitizesRichText;

    protected $fillable = ['classroom_id', 'course_id', 'uploaded_by', 'title', 'description', 'kind', 'price', 'is_free', 'visibility', 'file_path', 'status'];

    protected function casts(): array
    {
        return ['price' => 'integer', 'is_free' => 'boolean'];
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
}
