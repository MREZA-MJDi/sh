@extends('admin.layouts.app')
@section('title', 'داشبورد مدیریت')

@section('content')
@php
  $stats = $stats ?? [];
  $weeklyAttendance = $weeklyAttendance ?? collect();
  $attendanceTotal = (int) ($attendanceTotal ?? 0);
  $pendingHomework = (int) ($stats['pending_homework'] ?? 0);
  $openMessages = (int) ($openMessageCount ?? 0);
  $pendingConsultations = (int) ($pendingConsultationCount ?? 0);
  $averageCoursePurchase = (int) ($averageCoursePurchase ?? 0);
  $paidCoursePurchases = (int) ($paidCoursePurchases ?? 0);
  $revenueLast30Days = (int) ($revenueLast30Days ?? 0);
@endphp

<section class="adm-welcome" aria-labelledby="dashboard-title">
  <div class="adm-welcome-copy">
    <span class="adm-welcome-kicker">مرکز فرماندهی آکادمی</span>
    <h1 id="dashboard-title">سلام {{ auth()->user()?->name ?? 'مدیر عزیز' }}، خوش اومدی</h1>
    <p>وضعیت امروز آکادمی را یک‌جا ببین و کارهای مهم را بدون گشتن بین منوها انجام بده.</p>
    <div class="adm-welcome-actions">
      @can('courses.manage')
        <x-admin.btn icon="plus" :href="route('admin.courses.create')">ساخت دوره</x-admin.btn>
      @endcan
      @can('classes.manage')
        <x-admin.btn variant="line" icon="cls" :href="route('admin.classes.index')">رفتن به کلاس‌ها</x-admin.btn>
      @endcan
    </div>
  </div>
  <aside class="adm-welcome-aside adm-sales-overview" aria-label="خلاصه فروش دوره‌ها">
    <div class="adm-sales-heading">
      <span class="adm-sales-icon"><x-admin.icon name="chart"/></span>
      <div><small>فروش واقعی دوره‌ها</small><b>میانگین خرید تأییدشده</b></div>
    </div>
    <strong class="adm-sales-average">{{ number_format($averageCoursePurchase) }} <small>تومان</small></strong>
    <div class="adm-sales-foot">
      <span><b>{{ number_format($paidCoursePurchases) }}</b> خرید تأییدشده</span>
      <span><b>{{ number_format($revenueLast30Days) }}</b> تومان فروش ۳۰ روز اخیر</span>
    </div>
    @if($paidCoursePurchases === 0)
      <p class="adm-sales-empty">هنوز خرید دوره‌ای با پرداخت تأییدشده ثبت نشده است.</p>
    @endif
  </aside>
</section>

<div class="adm-stats">
  <x-admin.stat tone="users" icon="user" label="دانش‌آموزان فعال" :value="number_format($stats['students'] ?? 0)" hint="در محدوده دسترسی"/>
  <x-admin.stat tone="edu" icon="book" label="دوره‌های منتشرشده" :value="number_format($stats['courses'] ?? 0)" hint="قابل مشاهده برای دانش‌آموزان"/>
  <x-admin.stat tone="edu" icon="cls" label="گروه‌های آموزشی فعال" :value="number_format($stats['classes'] ?? 0)" hint="کلاس‌های در حال برگزاری"/>
  <x-admin.stat tone="track" icon="file" label="پاسخ‌های منتظر بررسی" :value="number_format($pendingHomework)" hint="نیازمند اقدام مدرس"/>
  <x-admin.stat tone="users" icon="users" label="مدرسان فعال" :value="number_format($stats['teachers'] ?? 0)" hint="حساب‌های فعال"/>
  <x-admin.stat tone="shop" icon="chart" label="درآمد تأییدشده ۳۰ روز اخیر" :value="number_format($revenueLast30Days)" hint="تومان · پرداخت‌های موفق دوره"/>
</div>

<section class="adm-stack" aria-label="دسترسی سریع">
  <div class="adm-pagehead">
    <div><h2>میانبرهای سریع</h2><p>کارهای پرتکرار را مستقیم باز کن.</p></div>
  </div>
  <div class="adm-action-grid">
    @can('users.manage')
      <a class="adm-action-tile" href="{{ route('admin.students.index') }}"><span><x-admin.icon name="users"/></span><div><b>دانش‌آموزان</b><small>پرونده و وضعیت کاربران</small></div><x-admin.icon name="chev"/></a>
      <a class="adm-action-tile" href="{{ route('admin.teachers.index') }}"><span><x-admin.icon name="user"/></span><div><b>مدرسان</b><small>مدیریت اعضای آموزشی</small></div><x-admin.icon name="chev"/></a>
    @endcan
    @can('courses.manage')
      <a class="adm-action-tile" href="{{ route('admin.courses.index') }}"><span><x-admin.icon name="book"/></span><div><b>دوره‌ها</b><small>محتوا و انتشار دوره‌ها</small></div><x-admin.icon name="chev"/></a>
    @endcan
    @canany(['attendance.manage.all', 'attendance.manage.assigned', 'attendance.view.assigned'])
      <a class="adm-action-tile" href="{{ route('admin.attendance.index') }}"><span><x-admin.icon name="check"/></span><div><b>حضور و غیاب</b><small>ثبت وضعیت جلسات</small></div><x-admin.icon name="chev"/></a>
    @endcan
    @canany(['homework.manage.all', 'homework.manage.assigned', 'homework.view.assigned'])
      <a class="adm-action-tile" href="{{ route('admin.homework.index') }}"><span><x-admin.icon name="file"/></span><div><b>تکالیف</b><small>ساخت و بررسی تمرین‌ها</small></div><x-admin.icon name="chev"/></a>
    @endcan
    @canany(['messages.view.all', 'messages.view.assigned'])
      <a class="adm-action-tile" href="{{ route('admin.communication-center.index') }}"><span><x-admin.icon name="msg"/></span><div><b>مرکز ارتباطات</b><small>پیام‌ها و درخواست‌ها</small></div><x-admin.icon name="chev"/></a>
    @endcanany
    @can('integrations.manage')
      <a class="adm-action-tile" href="{{ route('admin.spotplayer.index') }}"><span><x-admin.icon name="video"/></span><div><b>اتصال ویدیو</b><small>مدیریت سرویس آموزشی</small></div><x-admin.icon name="chev"/></a>
    @endcan
  </div>
</section>

<div class="adm-split is-even">
  <x-admin.panel title="کارهایی که منتظر تو هستند" desc="موارد باز را ببین و با یک کلیک به همان بخش برو.">
    <div class="adm-work-queue">
      @php $hasWork = false; @endphp
      @can('homework.grade')
        @php $hasWork = $hasWork || $pendingHomework > 0; @endphp
        @if($pendingHomework > 0)
          <a class="adm-work-item" data-tone="track" href="{{ route('admin.homework.index') }}"><span><x-admin.icon name="file"/></span><div><b>تصحیح پاسخ تکالیف</b><small>پاسخ دانش‌آموزان منتظر بررسی است</small></div><strong>{{ number_format($pendingHomework) }}</strong></a>
        @endif
      @endcan
      @canany(['messages.view.all', 'messages.view.assigned'])
        @php $hasWork = $hasWork || $openMessages > 0; @endphp
        @if($openMessages > 0)
          <a class="adm-work-item" data-tone="comm" href="{{ route('admin.communication-center.index') }}"><span><x-admin.icon name="msg"/></span><div><b>گفت‌وگوهای باز</b><small>پیام‌هایی که نیاز به پاسخ دارند</small></div><strong>{{ number_format($openMessages) }}</strong></a>
        @endif
      @endcanany
      @php $hasWork = $hasWork || $pendingConsultations > 0; @endphp
      @if($pendingConsultations > 0)
        <a class="adm-work-item" data-tone="comm" href="{{ route('admin.consultations.index') }}"><span><x-admin.icon name="chat"/></span><div><b>درخواست‌های مشاوره جدید</b><small>برای درخواست‌ها زمان‌بندی تعیین کن</small></div><strong>{{ number_format($pendingConsultations) }}</strong></a>
      @endif
      @if(($publishedAnnouncementCount ?? 0) > 0)
        @php $hasWork = true; @endphp
        <a class="adm-work-item" data-tone="edu" href="{{ route('admin.announcements.index') }}"><span><x-admin.icon name="bell"/></span><div><b>اطلاعیه‌های منتشرشده</b><small>مدیریت اطلاع‌رسانی آکادمی</small></div><strong>{{ number_format($publishedAnnouncementCount) }}</strong></a>
      @endif
      @if(!$hasWork)
        <div class="adm-queue-empty"><x-admin.icon name="check"/><b>فعلاً کار فوری در صف نیست</b><span>همه‌چیز آرام است؛ می‌توانی وضعیت دوره‌ها و کلاس‌ها را مرور کنی.</span></div>
      @endif
    </div>
  </x-admin.panel>

  <x-admin.panel title="ترکیب حضور و غیاب در هفته" desc="تعداد واقعی وضعیت‌های ثبت‌شده در هر روز؛ جلسات لغوشده یا هنوز برگزارنشده و وضعیت‌های نامعتبر محاسبه نمی‌شوند.">
    <div class="adm-week-chart" role="group" aria-label="نمودار هفتگی تفکیک وضعیت حضور و غیاب">
        @foreach($weeklyAttendance as $day)
          <div class="adm-week-day" aria-label="{{ $day['label'] }} {{ $day['date_full_label'] }}: {{ $day['total'] }} ثبت">
            <div class="adm-week-track" title="{{ $day['label'] }} {{ $day['date_full_label'] }} · {{ number_format($day['total']) }} ثبت">
              @foreach(['present' => 'حاضر', 'late' => 'تأخیر', 'absent' => 'غایب', 'excused' => 'موجه'] as $key => $label)
                @php
                  $count = (int) $day[$key];
                  $share = $day['total'] > 0 ? round(($count / $day['total']) * 100, 2) : 0;
                @endphp
                @if($count > 0)
                  <span class="adm-week-segment is-{{ $key }}" style="--segment-size:{{ $share }}%" title="{{ $label }}: {{ number_format($count) }}"></span>
                @endif
              @endforeach
            </div>
            <b>{{ $day['label'] }}</b>
            <small class="adm-week-date">{{ $day['date_label'] }}</small>
            <small>{{ number_format($day['total']) }} ثبت</small>
          </div>
        @endforeach
      </div>
      <div class="adm-week-legend" aria-label="راهنمای نمودار">
        <span><i class="is-present"></i>حاضر</span>
        <span><i class="is-late"></i>تأخیر</span>
        <span><i class="is-absent"></i>غایب</span>
        <span><i class="is-excused"></i>موجه</span>
      </div>
    @if($attendanceTotal === 0)
      <p class="adm-muted" style="padding:0 4px 12px">در بازه هفت روز اخیر هنوز رکورد حضور و غیاب ثبت نشده است؛ روزها و تاریخ‌ها برای حفظ مقیاس زمانی نمایش داده می‌شوند.</p>
    @endif
  </x-admin.panel>
</div>
@endsection
