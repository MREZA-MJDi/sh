@extends('layouts.app')
@section('title', $p['title'])
@section('content')
@php $course = collect($m['courses'])->firstWhere('id', $p['course_id']); @endphp
<x-crumbs :items="['کتاب‌ها و محصولات' => $rt('shop.index'), $p['title'] => null]"/>
<div class="grid2">
  <x-card>
    <div class="th big t-{{ $course['tone'] }}"><x-icon name="book"/></div>
    <div class="chead"><h2>{{ $p['title'] }}</h2>@if($p['owned'])<x-tag>خریداری‌شده</x-tag>@endif</div>
    <p style="margin-bottom:12px">{{ $p['desc'] }}</p>
    <dl class="dl"><dt>نوع</dt><dd>{{ $p['kind'] }}</dd><dt>تعداد صفحه</dt><dd>{{ fa($p['pages']) }}</dd><dt>مرتبط با</dt><dd><a href="{{ $rt('courses.show', $course['id']) }}" style="text-decoration:underline">{{ $course['title'] }}</a></dd></dl>
  </x-card>
  <x-card title="{{ $p['owned'] ? 'دسترسی' : 'خرید' }}">
    @if($p['owned'])
      <p class="mu" style="margin-bottom:10px">این محصول در حساب شما فعال است.</p><a class="btn" href="#"><x-icon name="book"/>مطالعه</a>
    @else
      <p style="font-size:22px;font-weight:700;margin-bottom:10px">{{ toman($p['price']) }}</p>
      <a class="btn" href="#"><x-icon name="cart"/>{{ $role === 'parent' ? 'درخواست خرید' : 'افزودن به سبد خرید' }}</a>
    @endif
  </x-card>
</div>
@endsection
