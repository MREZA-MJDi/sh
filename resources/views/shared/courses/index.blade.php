@extends('layouts.app')
@section('title', $role === 'parent' ? 'پیشرفت دوره‌ها و کلاس‌ها' : 'دوره‌های من')
@section('content')
@if($role === 'parent')<div class="info">گزارش مربوط به <b>{{ $child['name'] }}</b> · {{ $child['grade'] }}</div>@endif
<div class="g3">
  @foreach($m['courses'] as $c)
    <x-card class="cc">
      <div class="th big t-{{ $c['tone'] }}"><x-icon name="book"/></div>
      <h3>{{ $c['title'] }}</h3>
      <p class="mu">{{ $c['teacher'] }} · {{ fa($c['total']) }} جلسه</p>
      <x-progress :value="$c['progress']"/>
      <p class="mu">{{ fa($c['progress']) }}٪ تکمیل · {{ fa($c['done']) }} از {{ fa($c['total']) }} جلسه</p>
      <a class="btn s" href="{{ $rt('courses.show', $c['id']) }}">{{ $role === 'parent' ? 'مشاهده‌ی گزارش' : 'ادامه‌ی یادگیری' }}</a>
    </x-card>
  @endforeach
</div>
@endsection
