<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PublicExamAttempt extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'expires_at' => 'datetime',
            'submitted_at' => 'datetime',
            'snapshot' => 'array',
            'answers' => 'array',
            'score_percent' => 'float',
        ];
    }

    protected static function booted(): void
    {
        static::creating(fn (self $attempt) => $attempt->uuid ??= (string) Str::uuid());
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function exam()
    {
        return $this->belongsTo(PublicExam::class, 'public_exam_id');
    }

    public function isFinished(): bool
    {
        return $this->submitted_at !== null;
    }

    public function isExpired(): bool
    {
        return now()->gt($this->expires_at->copy()->addSeconds(20));
    }

    public function remainingSeconds(): int
    {
        return max(0, (int) now()->diffInSeconds($this->expires_at, false));
    }

    public function sanitize(array $answers): array
    {
        $valid = collect($this->snapshot['questions'] ?? [])
            ->mapWithKeys(fn (array $question) => [$question['id'] => collect($question['options'])->pluck('id')->all()]);

        return collect($answers)->filter(function ($optionId, $questionId) use ($valid): bool {
            return $valid->has((int) $questionId)
                && in_array((int) $optionId, $valid->get((int) $questionId, []), true);
        })->map(fn ($optionId) => (int) $optionId)->all();
    }

    public function grade(array $answers): void
    {
        if ($this->isFinished()) {
            return;
        }

        $answers = $this->sanitize($answers);
        $correct = $wrong = $blank = 0;
        $earned = $total = 0;
        $negative = (bool) data_get($this->snapshot, 'settings.negative_marking', false);

        foreach (($this->snapshot['questions'] ?? []) as $question) {
            $points = (int) ($question['points'] ?? 1);
            $total += $points;
            $picked = $answers[$question['id']] ?? null;
            $right = collect($question['options'])->firstWhere('is_correct', true);

            if ($picked === null) {
                $blank++;
            } elseif ((int) $right['id'] === $picked) {
                $correct++;
                $earned += $points;
            } else {
                $wrong++;
                if ($negative) {
                    $earned -= $points / max(1, count($question['options']) - 1);
                }
            }
        }

        $this->update([
            'answers' => $answers,
            'submitted_at' => now(),
            'correct_count' => $correct,
            'wrong_count' => $wrong,
            'blank_count' => $blank,
            'score_percent' => $total > 0 ? round(max(0, $earned) / $total * 100, 2) : 0,
        ]);
    }
}
