@extends('student.layouts.app')
@section('title', 'مرکز یادگیری')
@section('content')
<section class="page" id="overview">
 <x-portal.page-head :title="'سلام '.$student->name.'!'" icon="home">
  @if($student->hasPermissionTo('lessons.view'))<a class="btn s" href="{{ route('student.courses.index') }}">ادامه یادگیری</a>@endif
 </x-portal.page-head>
 @include('components.announcement-feed', ['announcements' => $announcements ?? collect()])
 <div class="card">
  <div class="chead"><h2>برنامه و تکالیف</h2></div>
  <div class="portal-action-grid">
   <x-portal.action-card :href="route('student.live-classes')" title="جلسات و کلاس‌های آنلاین" icon="calendar" :value="number_format($upcoming_session_count)" :hint="($next_session ?? null)?->starts_at ? \App\Support\PersianDate::format($next_session->starts_at, 'Y/m/d H:i') : 'مشاهده برنامه جلسات'"/>
   @if($student->hasPermissionTo('homework.submit'))<x-portal.action-card :href="route('student.homework.index')" title="تکالیف من" hint="در انتظار ارسال" icon="check" tone="tracking" :value="number_format($pending_homework_count)"/>@endif
   @if($student->hasPermissionTo('lessons.view'))<x-portal.action-card :href="route('student.courses.index')" title="کلاس‌های فعال" icon="book" :value="number_format($classroom_count)"/>@endif
   @if($student->hasPermissionTo('exams.view.own'))<x-portal.action-card :href="route('student.exams.index')" title="آزمون‌ها و کارنامه" icon="chart" tone="tracking" :value="number_format($result_count)" hint="نتایج ثبت‌شده"/>@endif
  </div>
 </div>
 <div class="card">
  <div class="chead"><h2>ابزارهای یادگیری</h2></div>
  <div class="portal-action-grid">
   @if($student->hasPermissionTo('videos.view.enrolled'))<x-portal.action-card :href="route('student.videos.index')" title="ویدیوهای ضبط‌شده" icon="video"/>@endif
   @if($student->hasPermissionTo('resources.view.enrolled'))<x-portal.action-card :href="route('student.resources.index')" title="جزوه‌ها و منابع" icon="file"/>@endif
   <x-portal.action-card :href="route('student.library')" title="کتابخانه و خریدها" icon="wallet" tone="commerce"/>
   @if($student->hasPermissionTo('attendance.view.own'))<x-portal.action-card :href="route('student.attendance')" title="حضور و غیاب" icon="check" tone="tracking"/>@endif
   @if($student->hasPermissionTo('messages.view.own'))<x-portal.action-card :href="route('student.messages.index')" title="پیام‌ها و ارتباط با مدرس" icon="message" tone="communication"/>@endif
   <x-portal.action-card :href="route('student.meetings.index')" title="جلسات با اولیا" icon="calendar" tone="communication"/>
  </div>
 </div>
</section>
@endsection
