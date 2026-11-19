@extends('admin.layouts.app')
@section('title', 'داشبورد مدیریت')

@section('content')
@php
  $stats = $stats ?? [];
  $weeklyAttendance = $weeklyAttendance ?? collect();
@endphp

<x-admin.page-head title="داشبورد مدیریت" :desc="'سلام، '.(auth()->user()?->name ?? 'مدیر سامانه').' · نمای خلاصه و وضعیت امروز آکادمی'" icon="home"/>

<x-admin.panel title="میانبرهای سریع" desc="برای انجام کارهای روزانه، مستقیم وارد بخش مربوط شوید." data-tone="edu">
  <div class="adm-actions">
    @can('courses.manage')
      <x-admin.btn icon="book" :href="route('admin.courses.index')">مدیریت دوره‌ها</x-admin.btn>
    @endcan
    @can('classes.manage')
      <x-admin.btn variant="line" icon="cls" :href="route('admin.classes.index')">کلاس‌ها و جلسات</x-admin.btn>
    @endcan
    @can('users.manage')
      <x-admin.btn variant="line" icon="users" :href="route('admin.students.index')">دانش‌آموزان</x-admin.btn>
      <x-admin.btn variant="line" icon="user" :href="route('admin.teachers.index')">مدرسان</x-admin.btn>
    @endcan
    @can('homework.manage.all')
      <x-admin.btn variant="line" icon="file" :href="route('admin.homework.index')">تکالیف</x-admin.btn>
    @endcan
    @can('messages.view.all')
      <x-admin.btn variant="line" icon="msg" :href="route('admin.communication-center.index')">پیام‌ها</x-admin.btn>
    @endcan
    @can('integrations.manage')
      <x-admin.btn variant="line" icon="video" :href="route('admin.spotplayer.index')">ویدیوها و اتصال سرویس</x-admin.btn>
    @endcan
  </div>
</x-admin.panel>

<div class="adm-stats">
  <x-admin.stat tone="users" icon="user" label="دانش‌آموزان فعال" :value="number_format($stats['students'] ?? 0)" hint="در محدوده دسترسی"/>
  <x-admin.stat tone="edu" icon="book" label="دوره‌های منتشرشده" :value="number_format($stats['courses'] ?? 0)" hint="دوره‌های قابل مشاهده"/>
  <x-admin.stat tone="edu" icon="cls" label="گروه‌های آموزشی فعال" :value="number_format($stats['classes'] ?? 0)" hint="کلاس‌های فعال"/>
  <x-admin.stat tone="shop" icon="activity" label="نرخ حضور هفتگی" :value="number_format($stats['attendance_rate'] ?? 0).'٪'" hint="بر اساس حضور ثبت‌شده"/>
  <x-admin.stat tone="track" icon="file" label="تکالیف در انتظار بررسی" :value="number_format($stats['pending_homework'] ?? 0)" hint="پاسخ‌های ارسال‌شده"/>
  <x-admin.stat tone="users" icon="users" label="مدرسان فعال" :value="number_format($stats['teachers'] ?? 0)" hint="حساب‌های فعال"/>
</div>

<div class="adm-split is-even">
  <x-admin.panel title="نیاز به اقدام" desc="فقط تعداد موارد باز نمایش داده می‌شود؛ جزئیات در صفحه هر بخش قرار دارد.">
    <div class="adm-todo">
      @can('homework.grade')
        <a href="{{ route('admin.homework.index') }}" data-tone="track"><span class="adm-stat-ico"><x-admin.icon name="file"/></span>پاسخ‌های تکلیف در انتظار بررسی<strong class="adm-num">{{ number_format($stats['pending_homework'] ?? 0) }}</strong></a>
      @endcan
      @canany(['messages.view.all', 'messages.view.assigned'])
        <a href="{{ route('admin.communication-center.index') }}" data-tone="comm"><span class="adm-stat-ico"><x-admin.icon name="msg"/></span>گفت‌وگوهای باز<strong class="adm-num">{{ number_format($openMessageCount ?? 0) }}</strong></a>
      @endcanany
      @can('consultations.manage')
        <a href="{{ route('admin.consultations.index') }}" data-tone="comm"><span class="adm-stat-ico"><x-admin.icon name="chat"/></span>درخواست‌های مشاوره جدید<strong class="adm-num">{{ number_format($pendingConsultationCount ?? 0) }}</strong></a>
      @endcan
      @can('announcements.manage')
        <a href="{{ route('admin.announcements.index') }}" data-tone="edu"><span class="adm-stat-ico"><x-admin.icon name="bell"/></span>اطلاعیه‌های منتشرشده<strong class="adm-num">{{ number_format($publishedAnnouncementCount ?? 0) }}</strong></a>
      @endcan
    </div>
  </x-admin.panel>

  <x-admin.panel title="روند حضور هفتگی" desc="درصد حضور از داده‌های واقعی ثبت‌شده">
    <div class="adm-bars" data-tone="home">
      @forelse($weeklyAttendance as $day)
        <div><span class="adm-bar" style="height:{{ max(4, (int) $day['rate']) }}%"><b class="adm-num">{{ $day['rate'] }}٪</b></span>{{ $day['label'] }}</div>
      @empty
        <p class="adm-muted">هنوز داده حضور برای نمایش وجود ندارد.</p>
      @endforelse
    </div>
  </x-admin.panel>
</div>
@endsection
