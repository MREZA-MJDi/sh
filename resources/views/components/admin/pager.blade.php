{{-- Pagination. Static props now; with Laravel use: current=$items->currentPage() last=$items->lastPage() total=$items->total() per=$items->perPage() --}}
@props(['current' => 1, 'last' => null, 'total' => 0, 'per' => 10])
@php
  $last = $last ?? max(1, (int) ceil($total / $per));
  $from = $total ? ($current - 1) * $per + 1 : 0;
  $to = min($total, $current * $per);
  $pages = collect(range(1, $last))->filter(fn ($p) => $p === 1 || $p === $last || abs($p - $current) <= 1)->values();
  $href = fn ($p) => request()->fullUrlWithQuery(['page' => $p]);
@endphp
<div class="adm-pager">
  <div class="adm-pager-info adm-num">نمایش {{ $from }} تا {{ $to }} از {{ $total }} مورد</div>
  @if ($last > 1)
    <nav class="adm-pager-nav" aria-label="صفحه‌بندی">
      <a class="{{ $current <= 1 ? 'is-off' : '' }}" href="{{ $href(max(1, $current - 1)) }}" aria-label="قبلی">‹</a>
      @foreach ($pages as $i => $p)
        @if ($i > 0 && $p - $pages[$i - 1] > 1)<span class="is-gap">…</span>@endif
        <a class="adm-num {{ $p === $current ? 'is-on' : '' }}" href="{{ $href($p) }}" @if ($p === $current) aria-current="page" @endif>{{ $p }}</a>
      @endforeach
      <a class="{{ $current >= $last ? 'is-off' : '' }}" href="{{ $href(min($last, $current + 1)) }}" aria-label="بعدی">›</a>
    </nav>
  @endif
</div>
