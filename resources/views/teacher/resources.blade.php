@extends('teacher.layouts.app')
@section('title', 'منابع آموزشی')
@section('content')
@php($seasonLabels = ['spring' => 'بهار', 'summer' => 'تابستان', 'autumn' => 'پاییز', 'winter' => 'زمستان'])
<x-portal.page-head icon="book" tone="education" title="بارگذاری جزوه و منابع"/>
<div class="g g2">
<section class="c"><h2 class="portal-section-title"><x-portal.icon name="plus"/> ثبت منبع جدید</h2>
<form class="teacher-form" data-learning-scope method="POST" action="{{ route('teacher.resources.store') }}" enctype="multipart/form-data">@csrf
<label>عنوان منبع</label><input name="title" maxlength="180" required value="{{ old('title') }}">
<label>توضیحات</label><textarea name="description" rows="4">{{ old('description') }}</textarea>
<label>نوع منبع</label><select name="kind" required><option value="pdf" @selected(old('kind', 'pdf') === 'pdf')>PDF</option><option value="book" @selected(old('kind') === 'book')>کتاب</option><option value="other" @selected(old('kind') === 'other')>سایر</option></select>
<label>دوره</label><select name="course_id"><option value="">انتخاب دوره</option>@foreach($courses as $course)<option value="{{ $course->id }}" @selected((string) old('course_id') === (string) $course->id)>{{ $course->title }}@if($course->subject) · {{ $course->subject }}@endif @if($course->grade) · پایه {{ $course->grade }}@endif @if($course->season) · {{ $seasonLabels[$course->season] ?? $course->season }}@endif</option>@endforeach</select>
<label>کلاس (اختیاری)</label><select name="classroom_id"><option value="">سطح کل دوره</option>@foreach($classrooms as $classroom)<option value="{{ $classroom->id }}" @selected((string) old('classroom_id') === (string) $classroom->id) data-course-id="{{ $classroom->course_id }}">{{ $classroom->name }}</option>@endforeach</select>
<label>جلسه (اختیاری)</label><select name="class_session_id"><option value="">کل کلاس</option>@foreach($classrooms as $classroom)@foreach($classroom->sessions as $session)<option value="{{ $session->id }}" @selected((string) old('class_session_id') === (string) $session->id) data-classroom-id="{{ $classroom->id }}">{{ $session->title ?: $classroom->name }} · {{ $session->starts_at?->format('Y/m/d H:i') }}</option>@endforeach @endforeach</select>
<label>قیمت (تومان)</label><input type="number" name="price" min="0" value="{{ old('price',0) }}">
<label>دسترسی</label><select name="visibility"><option value="enrolled" @selected(old('visibility', 'enrolled') === 'enrolled')>دانش‌آموزان دارای دسترسی</option><option value="authenticated" @selected(old('visibility') === 'authenticated')>نیازمند ورود به حساب</option></select>
<small class="teacher-help">اگر کلاس یا جلسه‌ای انتخاب کنی، دسترسی به اعضای همان کلاس محدود می‌شود.</small>
<label>فایل</label><input type="file" name="file" accept=".pdf,.jpg,.jpeg,.png">
<label>وضعیت</label><select name="status"><option value="published" @selected(old('status', 'published') === 'published')>منتشرشده</option><option value="draft" @selected(old('status') === 'draft')>پیش‌نویس</option></select>
<input type="hidden" name="is_free" value="0">
<label><input type="checkbox" name="is_free" value="1" @checked((bool) old('is_free', true))> رایگان</label>
<button class="b" type="submit">ثبت منبع</button>
</form></section>
<section class="c"><div class="teacher-section-head"><div><h2 class="portal-section-title"><x-portal.icon name="book"/> منابع ثبت‌شده</h2><p>{{ $resources->total() }} منبع آموزشی</p></div></div>
<form method="GET" action="{{ route('teacher.resources') }}" class="teacher-list-filters">
<label class="teacher-search-field"><span class="sr-only">جست‌وجوی منبع</span><input type="search" name="q" value="{{ $filters['q'] ?? '' }}" maxlength="100" placeholder="جست‌وجوی عنوان منبع"></label>
<label><span class="sr-only">مرتب‌سازی</span><select name="sort"><option value="newest" @selected(($filters['sort'] ?? 'newest') === 'newest')>جدیدترین</option><option value="oldest" @selected(($filters['sort'] ?? '') === 'oldest')>قدیمی‌ترین</option><option value="title" @selected(($filters['sort'] ?? '') === 'title')>عنوان</option></select></label>
<button class="b s" type="submit">اعمال</button><a class="b s" href="{{ route('teacher.resources') }}">پاک‌کردن</a>
</form>
@forelse($resources as $resource)<div class="teacher-list-card"><div><strong>{{ $resource->title }}</strong><small>{{ $resource->course?->title ?? '—' }}@if($resource->course?->grade) · پایه {{ $resource->course->grade }}@endif @if($resource->course?->season) · {{ $seasonLabels[$resource->course->season] ?? $resource->course->season }}@endif · {{ $resource->classroom?->name ?? 'کل دوره' }}@if($resource->classSession) · {{ $resource->classSession->title }}@endif</small></div><div class="teacher-list-meta"><span>{{ $resource->is_free ? 'رایگان' : number_format($resource->price).' تومان' }}</span><span>{{ ['published' => 'منتشرشده', 'draft' => 'پیش‌نویس', 'archived' => 'بایگانی‌شده'][$resource->status] ?? 'در حال بررسی' }}</span>@if($resource->file_path)<a href="{{ route('teacher.resources.download',$resource) }}">مشاهده فایل</a>@endif</div></div>
@empty<x-portal.empty-state icon="file" title="هنوز منبعی برای دوره‌ها و کلاس‌های شما ثبت نشده است."/>@endforelse
{{ $resources->links() }}</section></div>
@endsection
