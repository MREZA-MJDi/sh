@extends('admin.layouts.app')
@section('title', 'مرکز مدیریت آموزش')

@section('content')
<x-admin.page-head title="مرکز مدیریت آموزش" icon="book" tone="edu"
  desc="دوره‌ای را باز کنید تا به محتوا، کلاس‌ها، جلسات و ثبت‌نام‌های مرتبط برسید.">
  <x-admin.btn :href="route('admin.courses.create')" icon="plus">ساخت دوره</x-admin.btn>
</x-admin.page-head>

<x-admin.panel title="دوره‌های آموزشی" desc="دوره موردنظر را پیدا کنید و وارد فضای مدیریت آن شوید." data-tone="edu">
  <form method="GET" action="{{ route('admin.lms.index') }}" class="adm-form">
    <x-admin.input label="جست‌وجوی عنوان دوره" name="q" :value="$filters['q'] ?? null"/>
    <x-admin.select label="وضعیت" name="status" :options="['draft' => 'پیش‌نویس', 'published' => 'منتشرشده', 'archived' => 'بایگانی']" :selected="$filters['status'] ?? null" placeholder="همه وضعیت‌ها"/>
    <div class="adm-actions">
      <x-admin.btn type="submit" icon="search">جست‌وجو</x-admin.btn>
      <x-admin.btn variant="line" :href="route('admin.lms.index')">پاک کردن فیلتر</x-admin.btn>
    </div>
  </form>
  <div class="adm-side-list">
    @forelse($courses as $course)
      <div>
        <span class="adm-stat-ico" data-tone="edu"><x-admin.icon name="book"/></span>
        <div class="adm-grow">
          <b>{{ $course->title }}</b>
          <small>{{ $course->teacher?->name ?? 'بدون مدرس' }} · {{ $course->sections_count }} فصل</small>
        </div>
        <x-admin.lms-course-status :status="$course->status"/>
        <x-admin.btn variant="line" :href="route('admin.lms.show', $course)">باز کردن دوره</x-admin.btn>
      </div>
    @empty
      <x-admin.empty icon="book" text="دوره‌ای با این فیلترها پیدا نشد."/>
    @endforelse
  </div>
  <x-slot:footer>{{ $courses->links() }}</x-slot:footer>
</x-admin.panel>
@endsection
