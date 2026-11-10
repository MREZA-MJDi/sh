@extends('layouts.app')
@section('title', $c['topic'])
@section('content')
@php $types = ['ticket' => 'تیکت', 'phone' => 'تماس تلفنی', 'in_person' => 'حضوری']; $step = ['open' => 0, 'scheduled' => 1, 'done' => 2][$c['status']]; @endphp
<x-crumbs :items="['درخواست مشاوره' => route('parent.consult.index'), $c['topic'] => null]"/>
<div class="grid2">
  <div class="stack">
    <x-card title="جزئیات درخواست"><x-slot:actions><x-status :s="$c['status']"/></x-slot:actions>
      <dl class="dl"><dt>نوع جلسه</dt><dd>{{ $types[$c['type']] }}</dd><dt>زمان پیشنهادی</dt><dd>{{ $c['preferred'] }}</dd><dt>زمان قطعی</dt><dd>{{ $c['scheduled'] ?? 'هنوز هماهنگ نشده' }}</dd><dt>پاسخ‌گو</dt><dd>{{ $c['handler'] }}</dd></dl>
      <p style="margin-top:12px">{{ $c['details'] }}</p>
    </x-card>
    <x-card title="گفتگو">
      <div class="msgs">@forelse($c['messages'] as $msg)<div class="m {{ $msg['from'] === 'me' ? 'me' : '' }}"><small>{{ $msg['name'] }} · {{ $msg['time'] }}</small>{{ $msg['text'] }}</div>@empty<div class="empty">هنوز پاسخی ثبت نشده است.</div>@endforelse</div>
      <form method="POST" action="{{ route('parent.consult.reply', $c['id']) }}">@csrf
        <div class="frm" style="align-items:flex-start"><div style="flex:1;min-width:220px"><label class="sr" for="body">پیام</label><textarea class="fld" id="body" name="body" style="min-height:70px" placeholder="پیام تکمیلی…"></textarea><x-field-error name="body"/></div><button class="btn" type="submit">ارسال</button></div>
      </form>
    </x-card>
  </div>
  <x-card title="پیگیری"><x-steps :items="['ثبت درخواست', 'هماهنگی زمان جلسه', 'برگزاری جلسه']" :current="$step"/></x-card>
</div>
@endsection
