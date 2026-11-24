@props(['title', 'description' => null, 'icon' => 'folder', 'tone' => 'education'])
<div {{ $attributes->merge(['class' => 'portal-empty-state', 'data-tone' => $tone]) }}>
    <span class="portal-state-icon" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
            @switch($icon)
                @case('calendar') <rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18M8 15h2m4 0h2"/> @break
                @case('video') <rect x="3" y="5" width="18" height="14" rx="3"/><path d="m10 9 5 3-5 3z"/> @break
                @case('message') <path d="M21 11.5a8.5 8.5 0 0 1-8.5 8.5H4l1.5-4A8.5 8.5 0 1 1 21 11.5Z"/><path d="M8 11h8m-8 4h5"/> @break
                @case('file') <path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9zM14 3v6h6M8 13h8m-8 4h6"/> @break
                @default <path d="M3 7a2 2 0 0 1 2-2h5l2 2h7a2 2 0 0 1 2 2v10H3z"/>
            @endswitch
        </svg>
    </span>
    <strong>{{ $title }}</strong>
    @if($description)<p>{{ $description }}</p>@endif
    @if($slot->isNotEmpty())<div class="portal-state-actions">{{ $slot }}</div>@endif
</div>
