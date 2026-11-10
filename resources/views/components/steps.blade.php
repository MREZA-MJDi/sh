@props(['items', 'current' => 0])
<ol class="steps">
  @foreach($items as $i => $label)
    <li @class(['done' => $i < $current, 'now' => $i === $current])><i>@if($i < $current)✓@else{{ \App\Support\PersianDate::toPersianDigits($i + 1) }}@endif</i>{{ $label }}</li>
  @endforeach
</ol>
