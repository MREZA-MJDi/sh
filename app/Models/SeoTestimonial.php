<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeoTestimonial extends Model
{
    protected $fillable = ['created_by', 'name', 'role', 'quote', 'rating', 'status', 'sort_order'];

    protected function casts(): array
    {
        return ['rating' => 'integer', 'sort_order' => 'integer'];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
