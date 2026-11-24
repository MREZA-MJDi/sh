@extends('admin.layouts.app')
@section('title', $course->title)

@section('content')
<x-admin.page-head :title="$course->title" icon="book" tone="edu"
  :back-url="route('admin.lms.index')" back-label="مرکز مدیریت آموزش">
  <x-admin.lms-course-status :status="$course->status"/>
</x-admin.page-head>

<div class="adm-stack">
  <x-admin.panel title="فرایندهای دوره">
    <p>{{ $course->teacher?->name ?? 'مدرس هنوز مشخص نشده است.' }} · {{ $course->sections_count }} فصل در برنامهٔ آموزشی</p>
    <div class="adm-actions">
      @foreach($links as $link)
        <x-admin.btn variant="line" :icon="$link['icon']" :href="route($link['route'], $link['parameters'])">{{ $link['label'] }}</x-admin.btn>
      @endforeach
    </div>
  </x-admin.panel>

  @if($classrooms)
    <x-admin.panel title="کلاس‌ها و جلسات">
      <div class="adm-side-list">
        @forelse($classrooms as $classroom)
          <div>
            <div class="adm-grow"><b>{{ $classroom->name }}</b><small>{{ $classroom->students_count }} دانش‌آموز</small></div>
            <x-admin.btn variant="line" :href="route('admin.classes.sessions.index', $classroom)">برنامه جلسات</x-admin.btn>
            @if($canViewHomework)
              <x-admin.btn variant="line" :href="route('admin.homework.index', ['classroom_id' => $classroom->id])">تکالیف</x-admin.btn>
            @endif
            @if(auth()->user()->hasAnyPermission(['attendance.manage.all', 'attendance.manage.assigned', 'attendance.view.assigned']))
              <x-admin.btn variant="line" :href="route('admin.attendance.index', ['class_id' => $classroom->id])">حضور و غیاب</x-admin.btn>
            @endif
          </div>
        @empty
          <x-admin.empty text="برای این دوره هنوز کلاسی ثبت نشده است."/>
        @endforelse
      </div>
      <x-slot:footer>{{ $classrooms->links() }}</x-slot:footer>
    </x-admin.panel>
  @endif
</div>
@endsection
