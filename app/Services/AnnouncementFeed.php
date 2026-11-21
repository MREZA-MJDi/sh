<?php

namespace App\Services;

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

final class AnnouncementFeed
{
    public function forAudience(string $audience, ?User $user = null, int $limit = 5): Collection
    {
        $allowed = $audience === 'public' ? ['all'] : ['all', $audience];

        return Announcement::query()
            ->where('display_as_ticket', false)
            ->where('status', 'published')
            ->whereIn('audience', $allowed)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->where(fn ($query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
            ->when($user, fn ($query) => $query->withExists([
                'readers as is_read' => fn ($readers) => $readers->where('users.id', $user->id),
            ]))
            ->orderByRaw("CASE priority WHEN 'urgent' THEN 0 WHEN 'important' THEN 1 ELSE 2 END")
            ->orderByDesc('published_at')
            ->limit($limit)
            ->get();
    }

    public function publicTicket(): ?Announcement
    {
        return Announcement::query()
            ->where('display_as_ticket', true)
            ->where('audience', 'all')
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->where(fn ($query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
            ->orderByRaw("CASE priority WHEN 'urgent' THEN 0 WHEN 'important' THEN 1 ELSE 2 END")
            ->orderByDesc('published_at')
            ->first();
    }
}
