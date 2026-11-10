@props(['label', 'value', 'note' => null, 'accent' => '#7b8cff', 'icon' => null])
<article {{ $attributes->class(['panel-card', 'panel-stat']) }} style="--stat-accent: {{ $accent }}">
    @if ($icon)<div class="panel-stat-symbol">{{ $icon }}</div>@endif
    <span class="panel-stat-label">{{ $label }}</span>
    <strong class="panel-stat-value">{{ $value }}</strong>
    @if ($note)<span class="panel-stat-note">{{ $note }}</span>@endif
</article>
