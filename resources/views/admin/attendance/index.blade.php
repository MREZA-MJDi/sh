@extends('admin.layouts.app')
@section('title', 'حضور و غیاب')

@section('content')
@php
  $marks = ['present' => 'حاضر', 'late' => 'تأخیر', 'absent' => 'غایب', 'excused' => 'موجه'];
@endphp

<x-admin.page-head tone="track" icon="check" title="حضور و غیاب" desc="وضعیت حضور هر دانش‌آموز را برای جلسه انتخاب‌شده ثبت کن."/>

<form id="attendance-filter" method="GET" action="{{ route('admin.attendance.index') }}"></form>
<x-admin.panel flush>
  <form method="POST" action="{{ route('admin.attendance.store') }}" data-roster>
    @csrf
    <input type="hidden" name="class_id" value="{{ $classroom?->id }}">
    <input type="hidden" name="date" value="{{ $date }}">
    <input type="hidden" name="session" value="{{ $session?->id }}">

    <div class="adm-attendance-toolbar">
      <div class="adm-attendance-filters">
        <label class="adm-label">کلاس
          <select class="adm-select" name="class_id" form="attendance-filter" aria-label="انتخاب کلاس">
            @foreach ($classes as $v => $t)
              <option value="{{ $v }}" @selected((int) $classroom?->id === (int) $v)>{{ $t }}</option>
            @endforeach
          </select>
        </label>
        <label class="adm-label">تاریخ جلسه
          <input class="adm-input" type="date" form="attendance-filter" name="date" value="{{ $dateIso }}" aria-label="تاریخ جلسه">
        </label>
        <label class="adm-label">جلسه
          <select class="adm-select" name="session" form="attendance-filter" aria-label="انتخاب جلسه">
            @forelse ($sessions as $item)
              <option value="{{ $item->id }}" @selected($session?->id === $item->id)>{{ $item->title ?: 'جلسه' }} — {{ $item->starts_at?->format('H:i') }}</option>
            @empty
              <option value="">جلسه‌ای برای این تاریخ وجود ندارد</option>
            @endforelse
          </select>
        </label>
      </div>
      <div class="adm-actions">
        <x-admin.btn type="button" variant="line" size="sm" icon="check" data-mark-all="present">همه حاضر</x-admin.btn>
        <x-admin.btn type="submit" size="sm" icon="check" :disabled="!$session || $roster->isEmpty()">ذخیره وضعیت‌ها</x-admin.btn>
      </div>
    </div>

    <div class="adm-panel-body adm-attendance-counts">
      <div class="adm-attendance-summary" aria-live="polite">
        @foreach (['present' => 'حاضر', 'late' => 'تأخیر', 'absent' => 'غایب', 'excused' => 'موجه'] as $k => $t)
          <div data-t="{{ $k }}">{{ $t }} <b class="adm-num">0</b></div>
        @endforeach
      </div>
    </div>

    @if($roster->isNotEmpty())
      <div class="adm-roster-grid">
        @foreach ($roster as $student)
          <article class="adm-roster-card">
            <div class="adm-roster-person">
              <span class="adm-roster-avatar" aria-hidden="true">{{ mb_substr($student['name'], 0, 1) }}</span>
              <div><b>{{ $student['name'] }}</b><small>وضعیت این جلسه را انتخاب کن</small></div>
            </div>
            <div class="adm-marks" role="radiogroup" aria-label="وضعیت حضور {{ $student['name'] }}">
              @foreach ($marks as $value => $label)
                <label data-m="{{ $value }}">
                  <input type="radio" name="status[{{ $student['id'] }}]" value="{{ $value }}" @checked($student['status'] === $value) @if($loop->first) required @endif>
                  <span>{{ $label }}</span>
                </label>
              @endforeach
            </div>
            <label class="adm-sr" for="attendance-note-{{ $student['id'] }}">توضیح برای {{ $student['name'] }}</label>
            <input id="attendance-note-{{ $student['id'] }}" class="adm-input" name="note[{{ $student['id'] }}]" value="{{ $student['note'] }}" placeholder="توضیح اختیاری">
          </article>
        @endforeach
      </div>
    @else
      <div class="adm-queue-empty"><x-admin.icon name="calendar"/><b>دانش‌آموزی برای ثبت حضور پیدا نشد</b><span>یک کلاس و جلسه‌ی معتبر انتخاب کن یا ابتدا برای این تاریخ جلسه بساز.</span></div>
    @endif
  </form>
</x-admin.panel>

<x-admin.panel title="غیبت زیاد" desc="دانش‌آموزانی که حداقل سه جلسه غیبت داشته‌اند در این فهرست مشخص می‌شوند." flush>
  @if(count($report))
    <div class="adm-attendance-report">
      @foreach ($report as [$studentId, $classroomId, $name, $className, $total, $absent])
        <article class="adm-attendance-risk">
          <div class="adm-attendance-risk-head">
            <div class="adm-roster-person">
              <span class="adm-roster-avatar" aria-hidden="true">{{ mb_substr($name, 0, 1) }}</span>
              <div><b>{{ $name }}</b><small>{{ $className }}</small></div>
            </div>
            @if ($absent >= 3)
              <x-admin.badge tone="bad">غیبت زیاد</x-admin.badge>
            @else
              <x-admin.badge tone="warn">در حال پایش</x-admin.badge>
            @endif
          </div>
          <p>{{ number_format($absent) }} غیبت از {{ number_format($total) }} جلسه برگزارشده</p>
          <div class="adm-progress" data-tone="track"><i style="width:{{ $total > 0 ? min(100, round($absent / $total * 100)) : 0 }}%"></i></div>
          @if ($absent >= 3)
            <form method="POST" action="{{ route('admin.attendance.notify', [$classroomId, $studentId]) }}" style="margin-top:12px">
              @csrf
              <x-admin.btn type="submit" variant="soft" size="sm" icon="send">اطلاع به ولی</x-admin.btn>
            </form>
          @endif
        </article>
      @endforeach
    </div>
  @else
    <div class="adm-queue-empty"><x-admin.icon name="check"/><b>موردی برای پیگیری ثبت نشده</b><span>در حال حاضر گزارشی از غیبت‌های متوالی وجود ندارد.</span></div>
  @endif
</x-admin.panel>
@endsection
