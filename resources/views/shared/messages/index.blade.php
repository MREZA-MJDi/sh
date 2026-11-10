@extends('layouts.app')
@section('title', $role === 'parent' ? 'ارسال پیام' : 'پیام‌ها')
@section('content')
<x-card title="گفتگوها">
  <x-slot:actions><a class="btn s" href="{{ $rt('messages.create') }}">پیام جدید</a></x-slot:actions>
  <p class="mu" style="margin-bottom:8px">{{ $role === 'parent' ? 'انتقاد، پیشنهاد یا سؤال خود را برای اساتید، ادمین یا منشی آقای شیخان بفرستید.' : 'با اساتید، ادمین و منشی آقای شیخان گفتگو کنید.' }}</p>
  @forelse($threads as $t)
    <a class="trk" href="{{ $rt('messages.show', $t['id']) }}">
      <div class="th t-{{ ['a','b','c'][$t['id'] % 3] }}"><x-icon name="msg"/></div>
      <div><b>{{ $t['subject'] }}</b><span class="mu">{{ $t['to'] }} · {{ $t['updated'] }}</span></div>
      @if($t['unread'])<span class="badge">{{ fa($t['unread']) }}</span>@endif
    </a>
  @empty<div class="empty">هنوز پیامی ندارید. با «پیام جدید» شروع کنید.</div>@endforelse
</x-card>
@endsection
