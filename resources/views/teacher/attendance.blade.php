@extends('teacher.layouts.app')
@section('title', 'حضور و غیاب')
@section('content')
<h2>گزارش حضور و غیاب کلاس‌های من</h2>
<section class="c">
 @forelse ($attendance as $record)
  <div class="row"><div>{{ $record->student?->name }}<small>{{ $record->classroom?->name }} · {{ $record->session_date?->format('Y/m/d') }} · {{ $record->session?->starts_at?->format('H:i') }}</small></div>
   @php $status = ['present'=>['حاضر','gn'],'absent'=>['غایب','rd'],'late'=>['تأخیر','am']][$record->status] ?? ['ثبت‌شده','bl']; @endphp
   <i class="bd {{ $status[1] }}">{{ $status[0] }}</i>
  </div>
 @empty
  <p>رکورد حضور و غیابی برای کلاس‌های شما ثبت نشده است.</p>
 @endforelse
 {{ $attendance->links() }}
</section>
@endsection