@extends('student.layouts.app')

@section('title', 'مرکز یادگیری')

@section('content')
@php
    $sessions = $upcoming_sessions ?? collect();
    $tasks = $homeworks ?? collect();
    $courseItems = $course_progress ?? collect();
    $classItems = $classrooms ?? collect();
@endphp
<section class="page" id="overview">
 <div class="hero">
  <svg class="av" viewBox="0 0 120 120"><use href="#avatar"/></svg>
  <div class="txt">
   <h2>سلام {{ $student->name }}!</h2>
   <p>{{ $classroom_count }} کلاس فعال و {{ $pending_homework_count }} تکلیف ثبت‌نشده داری.</p>
   <a class="btn" href="{{ url('student/courses') }}">ادامه یادگیری</a>
  </div>
 </div>
 <div class="stats">
  <a class="st s1" href="{{ url('student/live-classes') }}" id="classes"><div class="ic"><svg class="i"><use href="#i-video"/></svg></div><small>کلاس‌های پیش‌رو</small><b>{{ $sessions->count() }}</b><span>جلسات برنامه‌ریزی‌شده</span></a>
  <a class="st s2" href="{{ url('student/homework') }}" id="homework"><div class="ic"><svg class="i"><use href="#i-task"/></svg></div><small>تکالیف نمایش‌داده‌شده</small><b>{{ $tasks->count() }}</b><span>{{ $pending_homework_count }} تکلیف بدون ارسال</span></a>
  <a class="st s3" href="{{ url('student/exams') }}" id="results"><div class="ic"><svg class="i"><use href="#i-exam"/></svg></div><small>نتایج اخیر</small><b>{{ $results->count() }}</b><span>نتایج قابل مشاهده</span></a>
  <a class="st s4" href="{{ url('student/attendance') }}" id="attendance"><div class="ic"><svg class="i"><use href="#i-msg"/></svg></div><small>نرخ حضور</small><b>{{ $attendance_rate === null ? '—' : $attendance_rate.'%' }}</b><span>حضور و غیاب ثبت‌شده</span></a>
 </div>
 <div class="row">
  <div class="card">
   <div class="chead"><h2>کلاس‌های پیش‌رو</h2><a class="pill" href="{{ url('student/live-classes') }}">همه</a></div>
   @forelse ($sessions as $session)
    <div class="trk">
     <div class="th" style="background:linear-gradient(160deg,#9bd3d0,#4b6fb3)"></div>
     <div><b>{{ $session->title ?: ($session->classroom?->name ?? 'کلاس') }}</b><span>{{ $session->session_type === 'onsite' ? 'حضوری' : 'آنلاین' }} · {{ $session->starts_at?->format('Y/m/d H:i') }}</span></div>
     @if ($session->session_type === 'online')
      @php
       $joinReady = filled($session->meeting_url)
        || ($session->meeting_provider === 'skyroom'
         && filled($session->classroom?->skyroom_room_id)
         && $session->classroom?->skyroom_status === 'ready');
       $joinWindowOpen = $session->starts_at && $session->ends_at
        && $session->starts_at->lte(now()->addMinutes(15))
        && $session->ends_at->gte(now());
      @endphp
      @if ($joinReady && $joinWindowOpen)
       <form method="POST" action="{{ route('student.class-sessions.join', $session) }}" target="_blank" rel="noopener">@csrf<button class="pl" type="submit" aria-label="پیوستن به کلاس آنلاین"><svg class="i"><use href="#i-play"/></svg></button></form>
      @elseif(! $joinReady)
       <span class="tag">{{ $session->meeting_provider === 'jitsi' ? 'لینک Jitsi هنوز ثبت نشده است' : 'لینک ورود پس از اتصال Skyroom فعال می‌شود' }}</span>
      @else
       <span class="tag">ورود از ۱۵ دقیقه پیش از شروع</span>
      @endif
     @else
      <span class="tag">حضوری</span>
     @endif
    </div>
   @empty
    <p>جلسه‌ی برنامه‌ریزی‌شده‌ای برای شما ثبت نشده است.</p>
   @endforelse
   @if ($classItems->isNotEmpty())
    <div class="chead"><h2>کلاس‌های ثبت‌شده</h2></div>
    @foreach ($classItems as $classroom)
     <div class="trk"><div><b>{{ $classroom->name }}</b><span>{{ $classroom->course?->title ?? 'کلاس فعال' }}</span></div></div>
    @endforeach
   @endif
  </div>
  <div class="card" id="homework-list">
   <div class="chead"><h2>تکالیف من</h2><a class="pill" href="{{ url('student/homework') }}">همه</a></div>
   @forelse ($tasks->take(5) as $homework)
    <div class="trk">
     <div class="th" style="background:linear-gradient(160deg,#c3d2dd,#6f8fb3)"></div>
     <div><b>{{ $homework->title }}</b><span>{{ $homework->classroom?->name }} · {{ $homework->due_at?->format('Y/m/d H:i') ?? 'بدون مهلت' }}</span></div>
     <span class="tag">{{ $homework->submitted_by_student_count ? 'ارسال‌شده' : 'در انتظار' }}</span>
     @if ($homework->submissions->first()?->score !== null)
      <span class="tag">نمره: {{ strtr(rtrim(rtrim(number_format((float) $homework->submissions->first()->score, 2, '.', ''), '0'), '.'), ['0'=>'۰','1'=>'۱','2'=>'۲','3'=>'۳','4'=>'۴','5'=>'۵','6'=>'۶','7'=>'۷','8'=>'۸','9'=>'۹']) }} از ۲۰</span>
     @endif
     @if ($homework->submissions->first()?->feedback)
      <p>نتیجه بررسی مدرس: {{ $homework->submissions->first()->feedback }}</p>
     @endif
    </div>
   @empty
    <p>تکلیفی برای کلاس‌های فعال شما ثبت نشده است.</p>
   @endforelse
  </div>
 </div>
 <div class="row">
  <div class="card">
   <div class="chead"><h2>پیشرفت دوره‌های من</h2><a class="pill" href="{{ url('student/courses') }}">همه</a></div>
   @forelse ($courseItems as $course)
    <div class="trk"><div><b>{{ data_get($course, 'title') }}</b><span>{{ strtr((string) data_get($course, 'completed_lessons', 0), ['0'=>'۰','1'=>'۱','2'=>'۲','3'=>'۳','4'=>'۴','5'=>'۵','6'=>'۶','7'=>'۷','8'=>'۸','9'=>'۹']) }} از {{ strtr((string) data_get($course, 'total_lessons', 0), ['0'=>'۰','1'=>'۱','2'=>'۲','3'=>'۳','4'=>'۴','5'=>'۵','6'=>'۶','7'=>'۷','8'=>'۸','9'=>'۹']) }} درس تکمیل‌شده</span></div><div class="prog"><i style="width:{{ (int) data_get($course, 'progress_percent', 0) }}%"></i></div></div>
   @empty
    <p>دوره‌ی فعالی برای نمایش وجود ندارد.</p>
   @endforelse
  </div>
  <div class="card" id="announcements">
   <div class="chead"><h2>اطلاعیه‌ها</h2></div>
   @forelse (($announcements ?? collect()) as $announcement)
    <div class="trk"><div><b>{{ $announcement->title }}</b><span>{{ $announcement->published_at?->format('Y/m/d') }}</span><p>{{ \Illuminate\Support\Str::limit(strip_tags($announcement->body), 150) }}</p></div></div>
   @empty
    <p>اطلاعیه‌ی تازه‌ای ثبت نشده است.</p>
   @endforelse
  </div>
 </div>

 <div class="row">
  <div class="card" id="exam-results">
   <div class="chead"><h2>نتایج و بازخورد آزمون‌ها</h2><a class="pill" href="{{ url('student/exams') }}">همه نتایج</a></div>
   @forelse($results as $result)
    <div class="trk">
     <div>
      <b>{{ $result->exam?->title ?? 'آزمون' }}</b>
      <span>{{ number_format((float) $result->score, 2) }} از {{ number_format((float) $result->max_score, 2) }} · {{ $result->synced_at?->format('Y/m/d H:i') ?? 'ثبت‌شده' }}</span>
      @if($result->feedback)<p>{{ $result->feedback }}</p>@endif
     </div>
     <span class="tag">کارنامه</span>
    </div>
   @empty
    <p>هنوز نتیجه‌ای برای نمایش ثبت نشده است.</p>
   @endforelse
  </div>
 </div>

 <div class="row">
  <div class="card" id="resources">
   <div class="chead"><h2>جزوه‌ها و منابع</h2><a class="pill" href="{{ route('student.resources.index') }}">همه منابع</a></div>
   @forelse (($resources ?? collect()) as $resource)
    <div class="trk"><div><b>{{ $resource->title }}</b><span>{{ $resource->course?->title ?? $resource->classroom?->name ?? 'منبع عمومی' }}</span></div>
     @if ($resource->file_path && $resource->downloadable)
      <a class="btn s" href="{{ route('student.resources.download', $resource) }}">دریافت</a>
     @else
      <span class="tag">{{ $resource->file_path ? 'دریافت غیرفعال' : 'بدون فایل' }}</span>
     @endif
    </div>
   @empty
    <p>منبع منتشرشده‌ای برای شما وجود ندارد.</p>
   @endforelse
  </div>
  <div class="card" id="notes">
   <div class="chead"><h2>یادداشت‌های من</h2></div>
   <form class="frm" method="POST" action="{{ route('student.notes.store') }}">@csrf
    <textarea class="fld" name="body" maxlength="10000" placeholder="یادداشت جدید..." required></textarea>
    <input type="hidden" name="tone" value="default"><button class="btn s" type="submit">افزودن یادداشت</button>
   </form>
   @forelse (($notes ?? collect()) as $note)
    <div class="trk"><div><p>{{ $note->body }}</p><small>{{ $note->created_at?->format('Y/m/d H:i') }}</small></div>
     <form method="POST" action="{{ route('student.notes.destroy', $note) }}">@csrf @method('DELETE')<button class="pill" type="submit">حذف</button></form>
    </div>
   @empty
    <p>یادداشتی ثبت نشده است.</p>
   @endforelse
  </div>
 </div>
</section>
@endsection
