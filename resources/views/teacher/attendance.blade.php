@extends('teacher.layouts.app')
@section('title', 'حضور و غیاب')
@section('content')
<x-portal.page-head icon="calendar" tone="tracking" title="حضور و غیاب"/>
<section class="c teacher-attendance-filter">
 @if($classrooms->isNotEmpty())
 <form method="GET" action="{{ route('teacher.attendance') }}" class="teacher-list-filters">
  <label><span>کلاس</span><select name="classroom_id">
   @foreach ($classrooms as $item)<option value="{{ $item->id }}" @selected($classroom?->id === $item->id)>{{ $item->name }}</option>@endforeach
  </select></label>
  <label><span>تاریخ جلسه</span><input type="date" name="date" value="{{ $dateIso }}"></label>
  <label><span>جلسه برگزارشده</span><select name="class_session_id">
   @foreach ($sessions as $item)<option value="{{ $item->id }}" @selected($session?->id === $item->id)>{{ $item->title ?: 'جلسه' }} · {{ $item->starts_at ? \App\Support\PersianDate::format($item->starts_at, 'Y/m/d H:i') : '—' }}</option>@endforeach
  </select></label>
  <button class="b s" type="submit">نمایش جلسه</button>
 </form>
 @else
 <div class="teacher-empty-state"><strong>هنوز کلاسی به شما تخصیص داده نشده است</strong><span>پس از تخصیص کلاس، ثبت حضور و غیاب از همین بخش در دسترس خواهد بود.</span></div>
 @endif
</section>
@if ($classroom && $session)
 <form method="POST" action="{{ route('teacher.attendance.store') }}">
  @csrf
  <input type="hidden" name="classroom_id" value="{{ $classroom->id }}">
  <input type="hidden" name="class_session_id" value="{{ $session->id }}">
  <section class="c teacher-attendance-roster">
   <div class="teacher-section-head"><div><h3>{{ $classroom->name }}</h3><p>{{ $session->title ?: 'جلسه آموزشی' }} · {{ $session->starts_at ? \App\Support\PersianDate::format($session->starts_at, 'Y/m/d H:i') : '—' }}</p></div><span class="teacher-result-count">{{ $studentPaginator?->total() ?? 0 }} دانش‌آموز</span></div>
   <p class="teacher-help">فهرست دانش‌آموزان صفحه‌بندی شده است. هر صفحه را جداگانه ذخیره کنید و سپس به صفحه بعد بروید.</p>
   @forelse ($students as $student)
    <div class="row"><div>{{ $student['name'] }}</div>
     <select name="attendance[{{ $student['id'] }}][status]" required @disabled(!auth()->user()->hasPermissionTo('attendance.manage.assigned'))>
      <option value="" disabled @selected(!$student['status'])>انتخاب وضعیت</option>
      <option value="present" @selected($student['status'] === 'present')>حاضر</option>
      <option value="late" @selected($student['status'] === 'late')>تأخیر</option>
      <option value="absent" @selected($student['status'] === 'absent')>غایب</option>
      <option value="excused" @selected($student['status'] === 'excused')>غیبت موجه</option>
     </select>
     <input type="datetime-local" name="attendance[{{ $student['id'] }}][joined_at]" value="{{ $student['joined_at'] }}" @disabled(!auth()->user()->hasPermissionTo('attendance.manage.assigned')) aria-label="زمان ورود">
     <input type="number" min="0" max="1440" name="attendance[{{ $student['id'] }}][attended_minutes]" value="{{ $student['attended_minutes'] }}" @disabled(!auth()->user()->hasPermissionTo('attendance.manage.assigned')) placeholder="دقیقه حضور">
     <input type="text" name="attendance[{{ $student['id'] }}][note]" value="{{ $student['note'] }}" @disabled(!auth()->user()->hasPermissionTo('attendance.manage.assigned')) placeholder="یادداشت">
    </div>
   @empty
    <p>دانش‌آموزی در این کلاس ثبت نشده است.</p>
   @endforelse
   {{ $studentPaginator?->links() }}
   @if ($students->isNotEmpty() && auth()->user()->hasPermissionTo('attendance.manage.assigned'))<button class="b" type="submit">ذخیره حضور و غیاب</button>@elseif($students->isNotEmpty())<p class="teacher-help">دسترسی شما فقط برای مشاهده حضور و غیاب است.</p>@endif
  </section>
 </form>
@endif
<section class="c">
 <div class="teacher-section-head"><div><h3>سوابق حضور و غیاب کلاس‌های من</h3><p>آخرین سوابق ثبت‌شده در کلاس‌های تخصیص‌یافته</p></div><span class="teacher-result-count">{{ $history->total() }} رکورد</span></div>
 @forelse ($history as $record)
  <div class="row"><div>{{ $record->student?->name }}<small>{{ $record->classroom?->name }} · {{ $record->session_date ? \App\Support\PersianDate::format($record->session_date, 'Y/m/d') : '—' }}</small></div>
   @php $status = ['present'=>['حاضر','gn'],'absent'=>['غایب','rd'],'late'=>['تأخیر','am'],'excused'=>['موجه','bl']][$record->status] ?? ['ثبت‌شده','bl']; @endphp
   <i class="bd {{ $status[1] }}">{{ $status[0] }}</i>
  </div>
 @empty
  <p>رکوردی ثبت نشده است.</p>
 @endforelse
 {{ $history->links() }}
</section>
@endsection
