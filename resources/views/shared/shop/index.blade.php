@extends('layouts.app')
@section('title', 'کتاب‌ها و محصولات')
@section('content')
<x-card title="محصولات مرتبط با کلاس‌های شما">
  <x-slot:actions><div class="tabs" data-tabs data-target="#shop"><button class="on" data-f="all">همه</button><button data-f="owned">خریداری‌شده</button><button data-f="sale">قابل خرید</button></div></x-slot:actions>
  <div class="g3" id="shop">
    @foreach($m['products'] as $p)
      <x-card class="cc" data-item data-cat="{{ $p['owned'] ? 'owned' : 'sale' }}">
        <div class="th big t-{{ collect($m['courses'])->firstWhere('id', $p['course_id'])['tone'] }}"><x-icon name="{{ $p['owned'] ? 'book' : 'cart' }}"/></div>
        <h3>{{ $p['title'] }}</h3><p class="mu">{{ $p['kind'] }} · {{ fa($p['pages']) }} صفحه</p>
        <p>@if($p['owned'])<x-tag>خریداری‌شده</x-tag>@else<b>{{ toman($p['price']) }}</b>@endif</p>
        <a class="btn s {{ $p['owned'] ? '' : 'g' }}" href="{{ $rt('shop.show', $p['id']) }}">{{ $p['owned'] ? 'مطالعه' : 'جزئیات و خرید' }}</a>
      </x-card>
    @endforeach
  </div>
</x-card>
@endsection
