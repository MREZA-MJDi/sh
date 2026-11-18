@extends('teacher.layouts.app')
@section('title', 'ویدئوهای آموزشی')
@section('content')
<div class="teacher-page-head"><div><span class="teacher-eyebrow">محتوای آموزشی</span><h1>بارگذاری فیلم‌ها</h1><p>ویدئو فقط به دوره‌ها و کلاس‌های تخصیص‌یافته به شما متصل می‌شود. اتصال رسمی SpotPlayer پس از دریافت API فعال خواهد شد.</p></div></div>
<div class="g g2">
<section class="c"><h2>ثبت ویدئوی جدید</h2>
<form class="teacher-form" method="POST" action="{{ route('teacher.spotplayer.videos.store') }}" enctype="multipart/form-data">@csrf
<label>عنوان ویدئو</label><input name="title" maxlength="180" required>
<label>دوره</label><select name="course_id" required><option value="">انتخاب دوره</option>@foreach($courses as $course)<option value="{{ $course->id }}">{{ $course->title }}</option>@endforeach</select>
<label>کلاس (اختیاری)</label><select name="classroom_id"><option value="">کل دوره</option>@foreach($classrooms as $classroom)<option value="{{ $classroom->id }}">{{ $classroom->name }}</option>@endforeach</select>
<label>شناسه ویدئوی SpotPlayer (در صورت نداشتن فایل)</label><input name="external_id" maxlength="190">
<label>فایل ویدئو (اختیاری)</label><input type="file" name="file" accept="video/mp4,video/quicktime,video/webm,video/x-matroska">
<input type="hidden" name="status" value="waiting_contract">
<button class="b" type="submit">ثبت ویدئو</button><small class="teacher-help">فایل در فضای خصوصی ذخیره می‌شود؛ تا زمان اتصال رسمی سرویس، لینک پخش ساختگی ایجاد نمی‌شود.</small>
</form></section>
<section class="c"><h2>ویدئوهای ثبت‌شده</h2>
@forelse($videos as $video)<div class="teacher-list-card"><div><strong>{{ $video->title }}</strong><small>{{ $video->course?->title }} · {{ $video->classroom?->name ?? 'کل دوره' }}</small></div><div class="teacher-list-meta"><span>{{ $video->status }}</span>@if($video->file_path)<a href="{{ route('teacher.spotplayer.videos.download',$video) }}">دریافت فایل مبدأ</a>@else<span>شناسه خارجی: {{ $video->external_id ?: 'ثبت نشده' }}</span>@endif</div></div>
@empty<p>هنوز ویدئویی برای دوره‌های شما ثبت نشده است.</p>@endforelse
{{ $videos->links() }}</section></div>
@endsection
