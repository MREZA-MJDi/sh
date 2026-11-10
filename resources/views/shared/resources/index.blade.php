@extends('layouts.app')
@section('title', $role === 'parent' ? 'منابع و محصولات' : 'جزوات و منابع')
@section('content')
<div class="info">فقط منابع و محصولات مرتبط با کلاس‌های ثبت‌نام‌شده نمایش داده می‌شود.</div>
@foreach($m['courses'] as $c)
  @php $items = collect($m['resources'])->where('course_id', $c['id']); @endphp
  @if($items->isNotEmpty())
    <x-card :title="$c['title']">
      <x-slot:actions><x-tag>{{ fa($items->count()) }} فایل</x-tag></x-slot:actions>
      @foreach($items as $r)
        <x-trk :icon="$r['type'] === 'pdf' ? 'file' : 'note'" :tone="$c['tone']" :title="$r['title']" :sub="strtoupper($r['type']).' · '.$r['size'].' · '.$r['date']"><a class="btn s" href="#"><x-icon name="download"/>دانلود</a></x-trk>
      @endforeach
    </x-card>
  @endif
@endforeach
<x-card title="محصولات قابل فروش">
  <x-slot:actions><a class="btn s g" href="{{ $rt('shop.index') }}">همه‌ی محصولات</a></x-slot:actions>
  <div class="g3">
    @foreach(collect($m['products'])->where('owned', false) as $p)
      <x-card class="cc"><h3>{{ $p['title'] }}</h3><p class="mu">{{ $p['kind'] }} · {{ toman($p['price']) }}</p><a class="btn s" href="{{ $rt('shop.show', $p['id']) }}">مشاهده</a></x-card>
    @endforeach
  </div>
</x-card>
@endsection
