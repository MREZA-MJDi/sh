@extends('admin.layouts.app')
@section('title', 'حضور و غیاب')

@section('content')
@php
  $marks = ['present' => 'حاضر', 'late' => 'تأخیر', 'absent' => 'غایب', 'excused' => 'موجه'];
@endphp

<x-admin.page-head tone="track" icon="check" title="حضور و غیاب" desc="ثبت حضور هر جلسه و بررسی غیبت دانش‌آموزان در همه دوره‌ها"/>

<form id="attendance-filter" method="GET" action="{{ route('admin.attendance.index') }}"></form>
<x-admin.panel flush data-tone="track">
  <form method="POST" action="{{ route('admin.attendance.store') }}" data-roster>
    @csrf
    <input type="hidden" name="class_id" value="{{ $classroom?->id }}">
    <input type="hidden" name="date" value="{{ $date }}">
    <input type="hidden" name="session" value="{{ $session?->id }}">
    <div class="adm-toolbar">
      <select class="adm-select" name="class_id" form="attendance-filter" aria-label="کلاس">
        @foreach ($classes as $v => $t)
          <option value="{{ $v }}" @selected((int) $classroom?->id === (int) $v)>{{ $t }}</option>
        @endforeach
      </select>
      <input class="adm-input" form="attendance-filter" name="date" value="{{ $date }}" style="width:140px" aria-label="تاریخ" autocomplete="off">
      <select class="adm-select" name="session" form="attendance-filter" aria-label="جلسه">
        @forelse ($sessions as $item)
          <option value="{{ $item->id }}" @selected($session?->id === $item->id)>
            {{ $item->title ?: 'جلسه' }} — {{ $item->starts_at?->format('H:i') }}
          </option>
        @empty
          <option value="">جلسه‌ای برای این تاریخ وجود ندارد</option>
        @endforelse
      </select>
      <div class="adm-toolbar-end">
        <x-admin.btn type="button" variant="line" size="sm" icon="check" data-mark-all="present">همه حاضر</x-admin.btn>
        <x-admin.btn type="submit" size="sm" icon="check" :disabled="!$session || $roster->isEmpty()">ذخیره حضور و غیاب</x-admin.btn>
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
        @forelse ($roster as $s)
          <tr>
            <td><x-admin.person :name="$s['name']"/></td>
            <td>
              <div class="adm-marks" role="radiogroup" aria-label="وضعیت {{ $s['name'] }}">
                @foreach ($marks as $v => $t)<label data-m="{{ $v }}"><input type="radio" name="status[{{ $s['id'] }}]" value="{{ $v }}" @checked($s['status'] === $v) @if($loop->first) required @endif><span>{{ $t }}</span></label>@endforeach
              </div>
            </td>
            <td><input class="adm-input" name="note[{{ $s['id'] }}]" value="{{ $s['note'] }}" placeholder="اختیاری" aria-label="توضیح" style="max-width:260px"></td>
          </tr>
        @empty
          <tr><td colspan="3">برای کلاس و جلسه انتخاب‌شده دانش‌آموزی جهت ثبت حضور وجود ندارد.</td></tr>
        @endforelse
      </tbody>
    </table></div>
  </form>
</x-admin.panel>

<x-admin.panel title="گزارش غیبت دانش‌آموزان" desc="دانش‌آموزی که ۳ جلسه یا بیشتر غایب باشد، علامت‌گذاری می‌شود" flush data-tone="track">
  <div class="adm-table-wrap"><table class="adm-table">
    <thead><tr><th>دانش‌آموز</th><th>کلاس</th><th>جلسات برگزارشده</th><th>غیبت</th><th></th></tr></thead>
    <tbody>
      @forelse ($report as [$studentId, $classroomId, $name, $cls, $total, $abs])
        <tr>
          <td><x-admin.person :name="$name"/></td><td>{{ $cls }}</td>
          <td class="adm-num">{{ $total }}</td>
          <td style="min-width:180px"><div class="adm-kv" style="justify-content:space-between"><b class="adm-num">{{ $abs }} جلسه</b>@if ($abs >= 3)<x-admin.badge tone="bad">غیبت زیاد</x-admin.badge>@endif</div><div class="adm-progress" data-tone="track"><i style="width:{{ $total > 0 ? round($abs / $total * 100) : 0 }}%"></i></div></td>
          <td class="is-end">
            @if ($abs >= 3)
              <form method="POST" action="{{ route('admin.attendance.notify', [$classroomId, $studentId]) }}">
                @csrf
                <x-admin.btn type="submit" variant="soft" size="sm" icon="send">اطلاع به ولی</x-admin.btn>
              </form>
            @endif
          </td>
        </tr>
      @empty
        <tr><td colspan="5">هنوز گزارشی از غیبت دانش‌آموزان ثبت نشده است.</td></tr>
      @endforelse
    </tbody>
  </table></div>
</x-admin.panel>
@endsection