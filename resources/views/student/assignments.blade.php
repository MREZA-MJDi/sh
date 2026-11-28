@extends('student.layouts.app')
@section('title', 'تکالیف')
@section('hide-topbar-title', '1')
@section('content')
<section class="page" id="assignments">
 <x-portal.page-head icon="check" tone="tracking" title="تکالیف">
  <x-slot:subtitle>اول راهنمای تکلیف را بخوان، بعد از دکمهٔ «ارسال پاسخ» جواب یا فایل تمرینت را بفرست.</x-slot:subtitle>
  <span class="pill">{{ number_format($homeworks->total()) }} تکلیف</span>
 </x-portal.page-head>
 <div class="assignment-list">
  @forelse($homeworks as $homework)
   @php
    $submission = $homework->submissions->first();
    $reviewed = $submission && ($submission->reviewed_at || in_array($submission->status, ['reviewed','graded'], true));
    $late = $homework->due_at && $homework->due_at->isPast();
    $state = $reviewed ? 'reviewed' : ($submission ? 'submitted' : ($late ? 'late' : 'pending'));
    $remaining = max(0, 5 - ($submission?->files->count() ?? 0));
    $restore = (string) old('homework_context') === (string) $homework->id;
   @endphp
   <article class="card assignment-card" id="homework-{{ $homework->id }}">
    <header class="assignment-heading">
     <span class="portal-state-icon"><x-portal.icon name="file"/></span>
     <div class="assignment-copy"><h2>{{ $homework->title }}</h2><small>{{ $homework->classroom?->name ?? 'کلاس آموزشی' }}</small></div>
     <x-portal.status domain="homework" :value="$state"/>
    </header>
    <div class="portal-metrics assignment-metrics">
     <div class="portal-metric"><small><x-portal.icon name="clock"/> مهلت ارسال</small><strong>{{ $homework->due_at ? \App\Support\PersianDate::format($homework->due_at, 'Y/m/d H:i') : 'بدون مهلت' }}</strong></div>
     @if($submission)<div class="portal-metric"><small><x-portal.icon name="calendar"/> زمان ارسال</small><strong>{{ $submission->submitted_at ? \App\Support\PersianDate::format($submission->submitted_at, 'Y/m/d H:i') : '—' }}</strong></div>@endif
     @if($reviewed && $submission->score !== null)<div class="portal-metric"><small><x-portal.icon name="chart"/> نمره</small><strong>نمره: {{ $submission->score }} از ۲۰</strong></div>@endif
    </div>
    @if(filled($homework->instructions))
     <div class="assignment-description"><h3>شرح تکلیف</h3><p>{{ html_entity_decode(strip_tags(preg_replace('/<\s*(?:br\s*\/?|\/p|\/li|\/div)\s*>/i', "\n", $homework->instructions)), ENT_QUOTES | ENT_HTML5, 'UTF-8') }}</p></div>
    @endif
    @if($homework->attachment_path)
     <a class="pill assignment-download" href="{{ route('student.homework.attachment', $homework) }}"><x-portal.icon name="file"/> مشاهده فایل تکلیف</a>
    @endif
    @if($submission?->answer)
     <div class="assignment-description"><h3>پاسخ شما</h3><p>{{ $submission->answer }}</p></div>
    @endif
    @if($submission?->files->isNotEmpty())
     <div class="assignment-files">
      @foreach($submission->files as $file)
       <div class="assignment-file">
        <a href="{{ route('student.homework.submission-files.download', $file) }}"><x-portal.icon name="file"/><span>{{ $file->original_name }}</span></a>
        @unless($reviewed)
         <form method="POST" action="{{ route('student.homework.submission-files.destroy', $file) }}">@csrf @method('DELETE')<button class="pill" type="submit" data-confirm="این فایل از پاسخ تکلیف حذف شود؟">حذف فایل</button></form>
        @endunless
       </div>
      @endforeach
     </div>
    @endif
    @if($reviewed && filled($submission->feedback))
     <div class="assignment-feedback"><h3>بازخورد مدرس</h3><p>{{ html_entity_decode(strip_tags($submission->feedback), ENT_QUOTES | ENT_HTML5, 'UTF-8') }}</p></div>
    @endif
    @unless($reviewed)
     <details class="assignment-editor" name="student-homework" @if($restore && $errors->any()) open @endif>
      <summary class="btn s"><x-portal.icon name="{{ $submission ? 'file' : 'plus' }}"/>{{ $submission ? 'ویرایش پاسخ' : 'ارسال پاسخ' }}</summary>
      <form class="assignment-form" method="POST" action="{{ route('student.homework.submissions.store', $homework) }}" enctype="multipart/form-data">
       @csrf
       <input type="hidden" name="homework_context" value="{{ $homework->id }}">
       @if($restore && $errors->any())
        <div class="assignment-feedback" role="alert">
         <h3>این موارد را بررسی کن</h3>
         <ul>@foreach($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul>
        </div>
       @endif
       <label for="answer-{{ $homework->id }}">جوابت را بنویس</label>
       <textarea class="fld" id="answer-{{ $homework->id }}" name="answer" rows="4" maxlength="12000" placeholder="متن پاسخ را بنویسید...">{{ $restore ? old('answer', $submission?->answer) : $submission?->answer }}</textarea>
       @if($remaining > 0)
        <label class="assignment-upload" for="files-{{ $homework->id }}">
         <x-portal.icon name="file"/><strong>انتخاب عکس یا فایل تمرین</strong><small>تا {{ $remaining }} فایل دیگر · بیشترین حجم هر فایل ۱۰ مگابایت</small>
         <input id="files-{{ $homework->id }}" type="file" name="files[]" multiple accept=".pdf,.jpg,.jpeg,.png,.txt">
        </label>
       @else
        <small class="assignment-meta">ظرفیت ۵ فایل تکمیل است؛ برای افزودن فایل، یکی از پیوست‌ها را حذف کنید.</small>
       @endif
       @if($late)<small class="assignment-meta">ارسال پس از مهلت مقرر ثبت می‌شود.</small>@endif
       <div class="assignment-form-actions"><button class="btn s" type="submit"><x-portal.icon name="check"/>{{ $submission ? 'ذخیره تغییرات پاسخ' : 'فرستادن پاسخ به معلم' }}</button></div>
      </form>
     </details>
    @else
     <span class="assignment-meta">پس از بررسی مدرس، پاسخ قابل ویرایش نیست.</span>
    @endunless
   </article>
  @empty
   <x-portal.empty-state icon="file" tone="tracking" title="تکلیفی برای کلاس‌های فعال شما ثبت نشده است."/>
  @endforelse
 </div>
 {{ $homeworks->links() }}
</section>
@endsection
