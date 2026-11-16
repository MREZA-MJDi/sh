@extends('student.layouts.app')
@section('title', 'حضور و غیاب')
@section('content')
<section class="page" id="attendance">
 <div class="card"><div class="chead"><h2>حضور و غیاب</h2><span class="tag">نرخ حضور: {{ $attendance_rate === null ? '—' : $attendance_rate.'٪' }}</span></div>
  <p>تعداد رکوردهای ثبت‌شده: {{ $attendance_count }}</p>
  @forelse (($attendance ?? collect()) as $record)
   <div class="trk"><div><b>{{ $record->classroom?->name ?? 'کلاس' }}</b><span>{{ $record->session_date?->format('Y/m/d') }} · {{ $record->session?->starts_at?->format('H:i') }}</span></div>
    @php $status = ['present' => ['حاضر','gn'], 'late' => ['تأخیر','am'], 'absent' => ['غایب','rd']][$record->status] ?? ['ثبت‌شده','bl']; @endphp
    <span class="tag {{ $status[1] }}">{{ $status[0] }}</span>
   </div>
  @empty
   <p>هنوز رکورد حضور و غیابی برای کلاس‌های شما ثبت نشده است.</p>
  @endforelse
 </div>
</section>
@endsection