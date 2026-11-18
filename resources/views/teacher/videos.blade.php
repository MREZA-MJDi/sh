@extends('teacher.layouts.app')
@section('title', 'ویدئوهای آموزشی')
@section('content')
@php($seasonLabels = ['spring' => 'بهار', 'summer' => 'تابستان', 'autumn' => 'پاییز', 'winter' => 'زمستان'])
<div class="teacher-page-head"><div><span class="teacher-eyebrow">محتوای آموزشی</span><h1>بارگذاری فیلم‌ها</h1><p>ویدئو فقط به دوره‌ها و کلاس‌های تخصیص‌یافته به شما متصل می‌شود. اتصال رسمی SpotPlayer پس از دریافت API فعال خواهد شد.</p></div></div>
<div class="g g2">
<section class="c"><h2>ثبت ویدئوی جدید</h2>
<form class="teacher-form" method="POST" action="{{ route('teacher.spotplayer.videos.store') }}" enctype="multipart/form-data">@csrf
<label>عنوان ویدئو</label><input name="title" maxlength="180" required>
<label>دوره</label><select name="course_id" required><option value="">انتخاب دوره</option>@foreach($courses as $course)<option value="{{ $course->id }}" >{{ $course->title }}@if($course->grade) · پایه {{ $course->grade }}@endif @if($course->season) · {{ $seasonLabels[$course->season] ?? $course->season }}@endif</option>@endforeach</select>
<label>کلاس (اختیاری)</label><select name="classroom_id"><option value="">کل دوره</option>@foreach($classrooms as $classroom)<option value="{{ $classroom->id }}">{{ $classroom->name }}</option>@endforeach</select>
<label>شناسه ویدئوی SpotPlayer (در صورت نداشتن فایل)</label><input name="external_id" maxlength="190" required>
<input type="hidden" name="status" value="waiting_contract">
<button class="b" type="submit">ثبت شناسه ویدئوی موجود</button><small class="teacher-help">برای ثبت ویدئوی موجود در SpotPlayer از شناسه خارجی استفاده کنید. برای بارگذاری فایل از آپلود امن تکه‌ای در بخش بعد استفاده کنید.</small>
</form>
<hr>
<h2>بارگذاری امن فایل جلسه</h2>
<div data-course-upload data-target="spotplayer_video" data-course-id="0"
  data-start-url="{{ route('teacher.uploads.start') }}" data-chunk-url="{{ route('teacher.uploads.chunk') }}"
  data-complete-url="{{ route('teacher.uploads.complete') }}" data-cancel-url="{{ route('teacher.uploads.cancel') }}"
  data-resume-url="{{ route('teacher.uploads.resume') }}" data-status-url="{{ route('teacher.uploads.status') }}"
  data-max-mb="{{ config('learning.max_lesson_mb') }}" data-chunk-mb="{{ config('learning.chunk_mb') }}">
  <label>عنوان جلسه</label><input data-upload-title maxlength="180" placeholder="مثلاً جلسه اول: مبانی ریاضی" required>
  <label>دوره</label><select data-upload-course required><option value="">انتخاب دوره</option>@foreach($courses as $course)<option value="{{ $course->id }}">{{ $course->title }}</option>@endforeach</select>
  <label>کلاس (اختیاری)</label><select data-upload-classroom><option value="">کل دوره</option>@foreach($classrooms as $classroom)<option value="{{ $classroom->id }}">{{ $classroom->name }}</option>@endforeach</select>
  <label>شناسه خارجی SpotPlayer (اختیاری)</label><input data-upload-external maxlength="190" placeholder="در صورت نیاز به اتصال بعدی">
  <label>فایل ویدیوی جلسه</label><input type="file" data-upload-file accept="video/mp4,video/quicktime,video/webm,video/x-matroska,video/x-m4v">
  <small class="teacher-help">حداکثر {{ config('learning.max_lesson_mb') }} مگابایت. آپلود قابل ادامه، ذخیره خصوصی و پردازش خودکار دارد.</small>
  <div class="teacher-progress" data-upload-progress hidden><span data-upload-bar style="width:0"></span></div>
  <p class="teacher-help" data-upload-status role="status" aria-live="polite">پس از انتخاب فایل، پیش‌نمایش و وضعیت آپلود در همین بخش نمایش داده می‌شود.</p>
  <button class="b" type="button" data-upload-start>بارگذاری و پردازش ویدیو</button>
</div>
</section>
<section class="c"><h2>ویدئوهای ثبت‌شده</h2>
@forelse($videos as $video)<div class="teacher-list-card"><div><strong>{{ $video->title }}</strong><small>{{ $video->course?->title }}@if($video->course?->grade) · پایه {{ $video->course->grade }}@endif @if($video->course?->season) · {{ $seasonLabels[$video->course->season] ?? $video->course->season }}@endif · {{ $video->classroom?->name ?? 'کل دوره' }}</small>@if($video->processing_error)<small class="teacher-help">{{ $video->processing_error }}</small>@endif</div><div class="teacher-list-meta"><span>{{ match ($video->status) { 'active' => 'آماده پخش', 'processing' => 'در حال پردازش', 'failed' => 'پردازش ناموفق', 'waiting_contract' => 'در انتظار قرارداد', default => $video->status } }}</span>@if($video->file_path)<a href="{{ route('teacher.spotplayer.videos.download',$video) }}">{{ $video->status === 'active' ? 'دریافت فایل پردازش‌شده' : 'دریافت فایل مبدأ' }}</a>@else<span>شناسه خارجی: {{ $video->external_id ?: 'ثبت نشده' }}</span>@endif</div></div>
@empty<p>هنوز ویدئویی برای دوره‌های شما ثبت نشده است.</p>@endforelse
{{ $videos->links() }}</section></div>
@endsection
