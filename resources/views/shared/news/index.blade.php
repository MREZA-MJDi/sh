@extends('layouts.app')
@section('title', 'اخبار مهم')
@section('content')
<x-card title="اخبار مهم آکادمی">
  <x-slot:actions><div class="tabs" data-tabs data-target="#news-list">
    <button class="on" data-f="all">همه</button>
    @foreach(collect($m['news'])->pluck('cat')->unique() as $cat)<button data-f="{{ $cat }}">{{ $cat }}</button>@endforeach
  </div></x-slot:actions>
  <p class="mu" style="margin-bottom:12px">اطلاعیه‌های عمومی که ادمین برای همه‌ی دانش‌آموزان و اولیاء منتشر می‌کند.</p>
  <div id="news-list">
    @foreach($m['news'] as $n)
      <a class="news" href="{{ $rt('news.show', $n['id']) }}" data-item data-cat="{{ $n['cat'] }}" style="display:block">
        <b>{{ $n['title'] }}</b><p>{{ $n['excerpt'] }}</p>
        <span class="mu">{{ $n['author'] }} · {{ $n['date'] }}</span> <x-tag :tone="$n['cat'] === 'محصول' ? 'd' : ($n['cat'] === 'اعلان' ? 'w' : '')">{{ $n['cat'] }}</x-tag>
      </a>
    @endforeach
  </div>
</x-card>
@endsection
