<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class HomeQuizQuestion extends Model
{
    protected $fillable = [
        'category', 'type', 'question', 'options', 'correct_answer',
        'explanation', 'sort_order', 'status',
    ];

    protected function casts(): array
    {
        return ['options' => 'array', 'sort_order' => 'integer'];
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')->orderBy('sort_order')->orderBy('id');
    }
}
