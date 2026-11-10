@props(['message' => 'موردی برای نمایش وجود ندارد.'])
<div {{ $attributes->class(['panel-empty']) }}>{{ $slot->isEmpty() ? $message : $slot }}</div>
