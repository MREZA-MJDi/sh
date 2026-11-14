@props(['label', 'value', 'note' => null, 'accent' => '#7b8cff', 'icon' => null, 'tone' => null])
<article {{ $attributes->class(['panel-card', 'panel-stat']) }} @if ($tone) data-tone="{{ $tone }}" @else style="--stat-accent: {{ $accent }}" @endif>
    @if ($icon)<div class="panel-stat-symbol">{{ $icon }}</div>@endif
    <span class="panel-stat-label">{{ $label }}</span>
    <strong class="panel-stat-value">{{ $value }}</strong>
    @if ($note)<span class="panel-stat-note">{{ $note }}</span>@endif
</article>
