@extends('teacher.layouts.app')
@section('title', 'منابع آموزشی')
@section('content')
@php($seasonLabels = ['spring' => 'بهار', 'summer' => 'تابستان', 'autumn' => 'پاییز', 'winter' => 'زمستان'])
<div class="teacher-page-head"><div><span class="teacher-eyebrow">محتوای آموزشی</span><h1>بارگذاری جزوه و منابع</h1><p>منابع فقط برای دوره‌ها و کلاس‌های تخصیص‌یافته به شما ثبت می‌شوند.</p></div></div>
<div class="g g2">
<section class="c"><h2>ثبت منبع جدید</h2>
<form class="teacher-form" data-learning-scope method="POST" action="{{ route('teacher.resources.store') }}" enctype="multipart/form-data">@csrf
<label>عنوان منبع</label><input name="title" maxlength="180" required value="{{ old('title') }}">
<label>توضیحات</label><textarea name="description" rows="4">{{ old('description') }}</textarea>
<label>نوع منبع</label><select name="kind" required><option value="pdf">PDF</option><option value="book">کتاب</option><option value="other">سایر</option></select>
<label>دوره</label><select name="course_id"><option value="">انتخاب دوره</option>@foreach($courses as $course)<option value="{{ $course->id }}" >{{ $course->title }}@if($course->subject) · {{ $course->subject }}@endif @if($course->grade) · پایه {{ $course->grade }}@endif @if($course->season) · {{ $seasonLabels[$course->season] ?? $course->season }}@endif</option>@endforeach</select>
<label>کلاس (اختیاری)</label><select name="classroom_id"><option value="">سطح کل دوره</option>@foreach($classrooms as $classroom)<option value="{{ $classroom->id }}" data-course-id="{{ $classroom->course_id }}">{{ $classroom->name }}</option>@endforeach</select>
<label>جلسه (اختیاری)</label><select name="class_session_id"><option value="">کل کلاس</option>@foreach($classrooms as $classroom)@foreach($classroom->sessions as $session)<option value="{{ $session->id }}" data-classroom-id="{{ $classroom->id }}">{{ $session->title ?: $classroom->name }} · {{ $session->starts_at?->format('Y/m/d H:i') }}</option>@endforeach @endforeach</select>
<label>قیمت (تومان)</label><input type="number" name="price" min="0" value="{{ old('price',0) }}">
<label>دسترسی</label><select name="visibility"><option value="enrolled">فقط دانش‌آموزان ثبت‌نام‌شده</option><option value="authenticated">کاربران واردشده</option></select>
<label>فایل</label><input type="file" name="file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.zip">
<label>وضعیت</label><select name="status"><option value="published">منتشرشده</option><option value="draft">پیش‌نویس</option></select>
<label><input type="checkbox" name="is_free" value="1" checked> رایگان</label>
<label><input type="checkbox" name="downloadable" value="1" checked> امکان دانلود</label>
<button class="b" type="submit">ثبت منبع</button>
</form></section>
<section class="c"><div class="teacher-section-head"><div><h2>منابع ثبت‌شده</h2><p>{{ $resources->total() }} منبع آموزشی</p></div></div>
<form method="GET" action="{{ route('teacher.resources') }}" class="teacher-list-filters">
<label class="teacher-search-field"><span class="sr-only">جست‌وجوی منبع</span><input type="search" name="q" value="{{ $filters['q'] ?? '' }}" maxlength="100" placeholder="جست‌وجوی عنوان منبع"></label>
<label><span class="sr-only">مرتب‌سازی</span><select name="sort"><option value="newest" @selected(($filters['sort'] ?? 'newest') === 'newest')>جدیدترین</option><option value="oldest" @selected(($filters['sort'] ?? '') === 'oldest')>قدیمی‌ترین</option><option value="title" @selected(($filters['sort'] ?? '') === 'title')>عنوان</option></select></label>
<button class="b s" type="submit">اعمال</button><a class="b s" href="{{ route('teacher.resources') }}">پاک‌کردن</a>
</form>
@forelse($resources as $resource)<div class="teacher-list-card"><div><strong>{{ $resource->title }}</strong><small>{{ $resource->course?->title ?? '—' }}@if($resource->course?->grade) · پایه {{ $resource->course->grade }}@endif @if($resource->course?->season) · {{ $seasonLabels[$resource->course->season] ?? $resource->course->season }}@endif · {{ $resource->classroom?->name ?? 'کل دوره' }}@if($resource->classSession) · {{ $resource->classSession->title }}@endif</small></div><div class="teacher-list-meta"><span>{{ $resource->is_free ? 'رایگان' : number_format($resource->price).' تومان' }}</span><span>{{ $resource->status }}</span>@if($resource->file_path && $resource->downloadable)<a href="{{ route('teacher.resources.download',$resource) }}">دریافت فایل</a>@endif</div></div>
@empty<p>هنوز منبعی برای دوره‌ها و کلاس‌های شما ثبت نشده است.</p>@endforelse
{{ $resources->links() }}</section></div>
@endsection
