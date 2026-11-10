@extends('layouts.app')
@section('title', 'پیام جدید')
@section('content')
<x-crumbs :items="['پیام‌ها' => $rt('messages.index'), 'پیام جدید' => null]"/>
<x-card title="ارسال پیام">
  <form method="POST" action="{{ $rt('messages.store') }}">@csrf
    <div class="fm">
      <div><label for="to">گیرنده</label>
        <select class="fld" id="to" name="to">
          <optgroup label="اساتید">@foreach(collect($m['teachers'])->where('id', '<=', 3) as $t)<option value="{{ $t['name'] }}" @selected(old('to') === $t['name'])>{{ $t['name'] }} ({{ $t['role'] }})</option>@endforeach</optgroup>
          <optgroup label="مدیریت"><option>{{ $m['teachers'][3]['name'] }}</option><option>{{ $m['academy']['secretary'] }}</option></optgroup>
        </select><x-field-error name="to"/></div>
      <div><label for="kind">نوع پیام</label><select class="fld" id="kind" name="kind"><option>سؤال</option><option>پیشنهاد</option><option>انتقاد</option><option>سایر</option></select></div>
      <div class="w"><label for="subject">موضوع</label><input class="fld" id="subject" name="subject" value="{{ old('subject') }}"><x-field-error name="subject"/></div>
      <div class="w"><label for="body">متن پیام</label><textarea class="fld" id="body" name="body" data-count="1500">{{ old('body') }}</textarea><x-field-error name="body"/></div>
    </div>
    <div class="frm"><button class="btn" type="submit">ارسال پیام</button><a class="btn g" href="{{ $rt('messages.index') }}">انصراف</a></div>
  </form>
</x-card>
@endsection
