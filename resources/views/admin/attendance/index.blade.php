@extends('admin.layouts.app')
@section('title', 'حضور و غیاب')

@section('content')
@php
  $classes = ['1' => 'ریاضی نهم — گروه الف', '2' => 'فیزیک دهم — گروه ب', '3' => 'زیست یازدهم — گروه ج'];
  $marks = ['present' => 'حاضر', 'late' => 'تأخیر', 'absent' => 'غایب', 'excused' => 'موجه'];
  $roster = $roster ?? [
    ['id' => 1, 'name' => 'سارا احمدی', 'status' => 'present', 'note' => ''],
    ['id' => 2, 'name' => 'علی رضایی', 'status' => 'late', 'note' => '۱۰ دقیقه تأخیر'],
    ['id' => 3, 'name' => 'نگار کریمی', 'status' => 'present', 'note' => ''],
    ['id' => 4, 'name' => 'رضا مرادی', 'status' => 'absent', 'note' => ''],
    ['id' => 5, 'name' => 'فاطمه حسینی', 'status' => 'excused', 'note' => 'گواهی پزشکی'],
    ['id' => 6, 'name' => 'کیان صالحی', 'status' => 'present', 'note' => ''],
  ];
  $report = $report ?? [
    ['رضا مرادی', 'ریاضی نهم — الف', 12, 4], ['علی رضایی', 'فیزیک دهم — ب', 10, 3], ['فاطمه حسینی', 'زیست یازدهم — ج', 9, 2], ['سارا احمدی', 'ریاضی نهم — الف', 12, 0],
  ];
@endphp

<x-admin.page-head tone="track" icon="check" title="حضور و غیاب" desc="ثبت حضور هر جلسه و بررسی غیبت دانش‌آموزان در همه دوره‌ها"/>

<form method="POST" action="{{ url('admin/attendance') }}" data-demo data-demo-message="حضور و غیاب ذخیره شد (نمایشی)">
  @csrf
  <x-admin.panel flush data-roster data-tone="track">
    <div class="adm-toolbar">
      <select class="adm-select" name="class_id" aria-label="کلاس">@foreach ($classes as $v => $t)<option value="{{ $v }}">{{ $t }}</option>@endforeach</select>
      <input class="adm-input" name="date" value="۱۴۰۵/۰۸/۲۵" style="width:140px" aria-label="تاریخ">
      <select class="adm-select" name="session" aria-label="جلسه"><option>جلسه ۱۲ — ۱۶:۰۰</option><option>جلسه ۱۱ — ۱۶:۰۰</option></select>
      <div class="adm-toolbar-end">
        <x-admin.btn variant="line" size="sm" icon="check" data-mark-all="present">همه حاضر</x-admin.btn>
        <x-admin.btn type="submit" size="sm" icon="check">ذخیره حضور و غیاب</x-admin.btn>
      </div>
    </div>
    <div class="adm-toolbar">
      <div class="adm-tally">
        @foreach (['present' => 'حاضر', 'late' => 'تأخیر', 'absent' => 'غایب', 'excused' => 'موجه'] as $k => $t)<div data-t="{{ $k }}">{{ $t }} <b class="adm-num">0</b></div>@endforeach
      </div>
    </div>
    <div class="adm-table-wrap"><table class="adm-table">
      <thead><tr><th>دانش‌آموز</th><th>وضعیت</th><th>توضیح</th></tr></thead>
      <tbody>
        @foreach ($roster as $s)
          <tr>
            <td><x-admin.person :name="$s['name']"/></td>
            <td>
              <div class="adm-marks" role="radiogroup" aria-label="وضعیت {{ $s['name'] }}">
                @foreach ($marks as $v => $t)<label data-m="{{ $v }}"><input type="radio" name="status[{{ $s['id'] }}]" value="{{ $v }}" @checked($s['status'] === $v)><span>{{ $t }}</span></label>@endforeach
              </div>
            </td>
            <td><input class="adm-input" name="note[{{ $s['id'] }}]" value="{{ $s['note'] }}" placeholder="اختیاری" aria-label="توضیح" style="max-width:260px"></td>
          </tr>
        @endforeach
      </tbody>
    </table></div>
  </x-admin.panel>
</form>

<x-admin.panel title="گزارش غیبت دانش‌آموزان" desc="دانش‌آموزی که ۳ جلسه یا بیشتر غایب باشد، علامت‌گذاری می‌شود" flush data-tone="track">
  <div class="adm-table-wrap"><table class="adm-table">
    <thead><tr><th>دانش‌آموز</th><th>کلاس</th><th>جلسات برگزارشده</th><th>غیبت</th><th></th></tr></thead>
    <tbody>
      @foreach ($report as [$name, $cls, $total, $abs])
        <tr>
          <td><x-admin.person :name="$name"/></td><td>{{ $cls }}</td>
          <td class="adm-num">{{ $total }}</td>
          <td style="min-width:180px"><div class="adm-kv" style="justify-content:space-between"><b class="adm-num">{{ $abs }} جلسه</b>@if ($abs >= 3)<x-admin.badge tone="bad">غیبت زیاد</x-admin.badge>@endif</div><div class="adm-progress" data-tone="track"><i style="width:{{ round($abs / $total * 100) }}%"></i></div></td>
          <td class="is-end">@if ($abs >= 3)<x-admin.btn variant="soft" size="sm" icon="send" data-toast="غیبت به ولی دانش‌آموز اطلاع داده شد (نمایشی)">اطلاع به ولی</x-admin.btn>@endif</td>
        </tr>
      @endforeach
    </tbody>
  </table></div>
</x-admin.panel>
@endsection
