@extends('layouts.app')
@section('title', 'درخواست مشاوره')
@section('content')
@php $types = ['ticket' => ['تیکت', 'ticket'], 'phone' => ['تماس تلفنی', 'phone'], 'in_person' => ['حضوری', 'pin']]; @endphp
<x-card title="درخواست‌های من">
  <x-slot:actions><a class="btn s" href="{{ route('parent.consult.create') }}">درخواست جدید</a></x-slot:actions>
  @forelse($m['consults'] as $c)
    <a class="trk" href="{{ route('parent.consult.show', $c['id']) }}">
      <div class="th t-{{ ['a','b','c'][$c['id'] % 3] }}"><x-icon :name="$types[$c['type']][1]"/></div>
      <div><b>{{ $c['topic'] }}</b><span class="mu">{{ $types[$c['type']][0] }} · {{ $c['scheduled'] ?? 'زمان هنوز هماهنگ نشده' }}</span></div>
      <x-status :s="$c['status']"/>
    </a>
  @empty<div class="empty">هنوز درخواستی ثبت نکرده‌اید.</div>@endforelse
</x-card>
@endsection
