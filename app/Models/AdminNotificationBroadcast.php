<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class AdminNotificationBroadcast extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'sender_id',
        'audience',
        'title',
        'message',
        'url',
        'action_label',
        'recipient_count',
        'recipient_max_id',
        'delivery_status',
    ];

    public static function recipients(string $audience): Builder
    {
        $role = match ($audience) {
            'students' => 'student',
            'parents' => 'parent',
            'teachers' => 'teacher',
            'admins' => 'admin',
            default => null,
        };

        return User::query()->where('is_active', true)
            ->when($role, fn ($query) => $query->whereHas('roles', fn ($roles) => $roles->where('name', $role)));
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
