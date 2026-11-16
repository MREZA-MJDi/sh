@extends('teacher.layouts.app')
@section('title', 'حضور و غیاب')
@section('content')
<h2>حضور و غیاب کلاس‌های من</h2>
<section class="c">
 <form method="GET" action="{{ route('teacher.attendance') }}">
  <label>کلاس</label><select name="classroom_id" onchange="this.form.submit()">
   @foreach ($classrooms as $item)<option value="{{ $item->id }}" @selected($classroom?->id === $item->id)>{{ $item->name }}</option>@endforeach
  </select>
  <label>جلسه برگزارشده</label><select name="class_session_id">
   @foreach ($sessions as $item)<option value="{{ $item->id }}" @selected($session?->id === $item->id)>{{ $item->title ?: 'جلسه' }} · {{ $item->starts_at?->format('Y/m/d H:i') }}</option>@endforeach
  </select>
  <button class="b s" type="submit">نمایش جلسه</button>
 </form>
</section>
@if ($classroom && $session)
 <form method="POST" action="{{ route('teacher.attendance.store') }}">
  @csrf
  <input type="hidden" name="classroom_id" value="{{ $classroom->id }}">
  <input type="hidden" name="class_session_id" value="{{ $session->id }}">
  <section class="c">
   <h3>{{ $classroom->name }} · {{ $session->starts_at?->format('Y/m/d H:i') }}</h3>
   @forelse ($students as $student)
    <div class="row"><div>{{ $student['name'] }}</div>
     <select name="attendance[{{ $student['id'] }}][status]">
      <option value="present" @selected($student['status'] === 'present')>حاضر</option>
      <option value="late" @selected($student['status'] === 'late')>تأخیر</option>
      <option value="absent" @selected($student['status'] === 'absent')>غایب</option>
      <option value="excused" @selected($student['status'] === 'excused')>غیبت موجه</option>
     </select>
     <input type="datetime-local" name="attendance[{{ $student['id'] }}][joined_at]" aria-label="زمان ورود">
     <input type="number" min="0" max="1440" name="attendance[{{ $student['id'] }}][attended_minutes]" placeholder="دقیقه حضور">
     <input type="text" name="attendance[{{ $student['id'] }}][note]" value="{{ $student['note'] }}" placeholder="یادداشت">
    </div>
   @empty
    <p>دانش‌آموزی در این کلاس ثبت نشده است.</p>
   @endforelse
   @if ($students->isNotEmpty())<button class="b" type="submit">ذخیره حضور و غیاب</button>@endif
  </section>
 </form>
@endif
<section class="c">
 <h3>سوابق حضور و غیاب کلاس‌های من</h3>
 @forelse ($history as $record)
  <div class="row"><div>{{ $record->student?->name }}<small>{{ $record->classroom?->name }} · {{ $record->session_date?->format('Y/m/d') }}</small></div>
   @php $status = ['present'=>['حاضر','gn'],'absent'=>['غایب','rd'],'late'=>['تأخیر','am'],'excused'=>['موجه','bl']][$record->status] ?? ['ثبت‌شده','bl']; @endphp
   <i class="bd {{ $status[1] }}">{{ $status[0] }}</i>
  </div>
 @empty
  <p>رکوردی ثبت نشده است.</p>
 @endforelse
</section>
@endsection
