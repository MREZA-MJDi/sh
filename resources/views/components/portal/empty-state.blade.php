@props(['title', 'description' => null, 'icon' => 'folder', 'tone' => 'education'])
<div {{ $attributes->merge(['class' => 'portal-empty-state', 'data-tone' => $tone]) }}>
    <span class="portal-state-icon" aria-hidden="true">
        <x-portal.icon :name="$icon"/>
    </span>
    <strong>{{ $title }}</strong>
    @if($description)<p>{{ $description }}</p>@endif
    @if($slot->isNotEmpty())<div class="portal-state-actions">{{ $slot }}</div>@endif
</div>
