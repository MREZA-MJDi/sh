<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class HomeworkSubmissionFile extends Model
{
    protected $fillable = ['homework_submission_id', 'path', 'original_name', 'mime_type', 'size'];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(HomeworkSubmission::class, 'homework_submission_id');
    }
}
