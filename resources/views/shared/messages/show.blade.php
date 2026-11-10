@extends('layouts.app')
@section('title', $t['subject'])
@section('content')
<x-crumbs :items="['پیام‌ها' => $rt('messages.index'), $t['subject'] => null]"/>
<x-card :title="'گفتگو با '.$t['to']">
  <div class="msgs">
    @foreach($t['messages'] as $msg)
      <div class="m {{ $msg['from'] === 'me' ? 'me' : '' }}"><small>{{ $msg['name'] }} · {{ $msg['time'] }}</small>{{ $msg['text'] }}</div>
    @endforeach
  </div>
  <form method="POST" action="{{ $rt('messages.reply', $t['id']) }}">@csrf
    <div class="frm" style="align-items:flex-start"><div style="flex:1;min-width:220px"><label class="sr" for="body">پاسخ</label><textarea class="fld" id="body" name="body" style="min-height:70px" placeholder="پاسخ خود را بنویسید…"></textarea><x-field-error name="body"/></div><button class="btn" type="submit">ارسال</button></div>
  </form>
</x-card>
@endsection
