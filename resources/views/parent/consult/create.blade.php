@extends('layouts.app')
@section('title', 'درخواست جلسه‌ی مشاوره')
@section('content')
<x-crumbs :items="['درخواست مشاوره' => route('parent.consult.index'), 'درخواست جدید' => null]"/>
<x-card title="ثبت درخواست">
  <form method="POST" action="{{ route('parent.consult.store') }}">@csrf
    <span class="lbl">نوع جلسه</span>
    <div class="choice">
      @foreach(['ticket' => ['تیکت (پیام‌رسانی)', 'ticket'], 'phone' => ['تماس تلفنی', 'phone'], 'in_person' => ['حضوری', 'pin']] as $v => [$label, $icon])
        <label><input type="radio" name="type" value="{{ $v }}" @checked(old('type', 'ticket') === $v)><div><x-icon :name="$icon"/>{{ $label }}</div></label>
      @endforeach
    </div><x-field-error name="type"/>
    <div class="fm" style="margin-top:16px">
      <div><label for="about">درباره‌ی فرزند</label><select class="fld" id="about" name="about">@foreach($m['children'] as $k)<option @selected($k['id'] === $child['id'])>{{ $k['name'] }}</option>@endforeach</select></div>
      <div><label for="preferred">زمان پیشنهادی</label><input class="fld" id="preferred" name="preferred" type="datetime-local" value="{{ old('preferred') }}"></div>
      <div><label for="phone">شماره‌ی تماس</label><input class="fld" id="phone" name="phone" value="{{ old('phone', $user['phone']) }}"><x-field-error name="phone"/></div>
      <div><label for="topic">موضوع</label><input class="fld" id="topic" name="topic" value="{{ old('topic') }}"><x-field-error name="topic"/></div>
      <div class="w"><label for="details">توضیحات</label><textarea class="fld" id="details" name="details" data-count="1000">{{ old('details') }}</textarea></div>
    </div>
    <div class="frm"><button class="btn" type="submit">ثبت درخواست</button><a class="btn g" href="{{ route('parent.consult.index') }}">انصراف</a></div>
  </form>
</x-card>
@endsection
