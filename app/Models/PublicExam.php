<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class PublicExam extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'negative_marking' => 'boolean',
            'shuffle' => 'boolean',
            'is_published' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $exam): void {
            $exam->slug ??= Str::slug($exam->title).'-'.Str::lower(Str::random(6));
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function questions()
    {
        return $this->hasMany(PublicExamQuestion::class)->orderBy('position');
    }

    public function attempts()
    {
        return $this->hasMany(PublicExamAttempt::class);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('is_published', true)
            ->where(fn (Builder $q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn (Builder $q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()));
    }

    public function levelLabel(): string
    {
        return [1 => 'آسان', 2 => 'متوسط', 3 => 'چالشی'][$this->level] ?? 'متوسط';
    }
}
