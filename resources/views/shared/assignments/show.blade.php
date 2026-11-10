@extends('layouts.app')
@section('title', $a['title'])
@section('content')
@php
$canSubmit = $role === 'student' && in_array($a['status'], ['pending', 'late']);
$step = ['pending' => 0, 'late' => 0, 'review' => 1, 'graded' => 3][$a['status']];
@endphp
<x-crumbs :items="['تکالیف' => $rt('assignments.index'), $a['title'] => null]"/>
<div class="grid2">
  <div class="stack">
    <x-card title="صورت تکلیف">
      <x-slot:actions><x-status :s="$a['status']"/></x-slot:actions>
      <p style="margin-bottom:14px">{{ $a['desc'] }}</p>
      <dl class="dl"><dt>درس</dt><dd>{{ $a['course'] }}</dd><dt>مهلت ارسال</dt><dd>{{ $a['due'] }}</dd><dt>بالاترین نمره</dt><dd>{{ fa($a['max']) }}</dd><dt>فرمت‌های مجاز</dt><dd>{{ $a['formats'] }}</dd></dl>
      @if($a['attachments'])
        <h3 style="margin:16px 0 6px">پیوست‌های مدرس</h3>
        @foreach($a['attachments'] as $f)<x-trk icon="file" tone="c" :title="$f"><a class="btn s g" href="#"><x-icon name="download"/>دانلود</a></x-trk>@endforeach
      @endif
    </x-card>

    @if($a['status'] === 'graded')
      <x-card title="نتیجه‌ی بررسی">
        <div class="donut">
          <div class="ring" style="background:conic-gradient(#58a8b4 {{ $a['score'] / $a['max'] * 360 }}deg,#c9d7e2 0)"><b>{{ fa($a['score']) }}</b></div>
          <div><p class="mu">نمره از {{ fa($a['max']) }}</p><p style="max-width:340px;margin-top:6px">«{{ $a['feedback'] }}»</p><p class="mu" style="margin-top:6px">ارسال‌شده از سوی ادمین برای دانش‌آموز و اولیاء</p></div>
        </div>
      </x-card>
    @endif

    @if($a['files'])
      <x-card title="فایل‌های ارسال‌شده">
        <p class="mu" style="margin-bottom:8px">ارسال: {{ $a['submitted'] ?? '—' }}</p>
        @foreach($a['files'] as $f)<x-trk icon="file" tone="a" :title="$f"/>@endforeach
      </x-card>
    @endif

    @if($canSubmit)
      <x-card title="بارگذاری تکلیف">
        @if($a['status'] === 'late')<div class="info w" style="margin-bottom:12px">مهلت این تکلیف گذشته است؛ ارسال با تأخیر ثبت می‌شود.</div>@endif
        <form method="POST" action="{{ route('student.assignments.submit', $a['id']) }}" enctype="multipart/form-data">
          @csrf
          <x-drop name="files"/><x-field-error name="files.0"/>
          <div class="sep">یا</div>
          <label class="lbl" for="text">پاسخ متنی</label>
          <textarea class="fld" id="text" name="text" data-count="2000" placeholder="پاسخ خود را بنویسید…">{{ old('text') }}</textarea><x-field-error name="text"/>
          <div class="frm"><button class="btn" type="submit"><x-icon name="upload"/>ارسال تکلیف</button><a class="btn g" href="{{ route('student.assignments.index') }}">انصراف</a></div>
        </form>
      </x-card>
    @endif
  </div>
  <x-card title="وضعیت تکلیف">
    <x-steps :items="['ارسال توسط دانش‌آموز', 'بررسی توسط ادمین', 'اعلام نتیجه به دانش‌آموز و اولیاء']" :current="$step"/>
    @if($role === 'parent' && in_array($a['status'], ['pending', 'late']))<div class="info w" style="margin-top:14px">این تکلیف هنوز ارسال نشده است.</div>@endif
  </x-card>
</div>
@endsection
