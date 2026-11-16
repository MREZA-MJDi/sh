@props(['text' => 'موردی پیدا نشد.', 'icon' => 'search'])
<div {{ $attributes->merge(['class' => 'adm-empty']) }}><x-admin.icon :name="$icon"/><p>{{ $text }}</p></div>
