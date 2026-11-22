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
  $weeklyTotals = collect($weeklyAttendance);
  $weeklyPresent = (int) $weeklyTotals->sum('present');
  $weeklyLate = (int) $weeklyTotals->sum('late');
  $weeklyAbsent = (int) $weeklyTotals->sum('absent');
  $weeklyExcused = (int) $weeklyTotals->sum('excused');
  $weeklyTotal = $weeklyPresent + $weeklyLate + $weeklyAbsent + $weeklyExcused;
  $weeklyShares = collect([$weeklyPresent, $weeklyLate, $weeklyAbsent, $weeklyExcused])
    ->map(fn ($count) => $weeklyTotal > 0 ? round(($count / $weeklyTotal) * 100, 2) : 0);
  $weeklyStops = [];
  $weeklyCursor = 0;
  foreach ($weeklyShares as $share) {
    $weeklyStops[] = number_format($weeklyCursor, 2, '.', '') . '% ' . number_format($weeklyCursor + $share, 2, '.', '') . '%';
    $weeklyCursor += $share;
  }
  $weeklyDonut = $weeklyTotal > 0
    ? 'conic-gradient(#22c55e ' . $weeklyStops[0] . ', #f59e0b ' . $weeklyStops[1] . ', #ef4444 ' . $weeklyStops[2] . ', #64748b ' . $weeklyStops[3] . ')'
    : 'conic-gradient(var(--line-strong) 0 100%)';
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
  <aside class="adm-welcome-aside adm-sales-overview" aria-label="ترکیب واقعی حضور و غیاب و خلاصه فروش">
    <div class="adm-dash-donut-wrap">
      <div class="adm-dash-donut" style="--donut-chart:{{ $weeklyDonut }}" role="img" aria-label="از مجموع {{ number_format($weeklyTotal) }} ثبت حضور هفته، {{ number_format($weeklyPresent) }} حاضر، {{ number_format($weeklyLate) }} تأخیر، {{ number_format($weeklyAbsent) }} غایب و {{ number_format($weeklyExcused) }} موجه">
        <div><strong>{{ number_format($weeklyTotal) }}</strong><small>ثبت این هفته</small></div>
      </div>
      <div class="adm-dash-donut-copy">
        <b>ترکیب حضور هفته</b>
        <small>بر اساس رکوردهای معتبر هفت روز اخیر</small>
        <div class="adm-dash-donut-legend">
          <span><i class="is-present"></i>حاضر <b>{{ number_format($weeklyPresent) }}</b></span>
          <span><i class="is-late"></i>تأخیر <b>{{ number_format($weeklyLate) }}</b></span>
          <span><i class="is-absent"></i>غایب <b>{{ number_format($weeklyAbsent) }}</b></span>
          <span><i class="is-excused"></i>موجه <b>{{ number_format($weeklyExcused) }}</b></span>
        </div>
      </div>
    </div>
    <div class="adm-sales-foot">
      <span>میانگین خرید تأییدشده <b>{{ number_format($averageCoursePurchase) }} تومان</b></span>
      <span>تعداد خرید موفق <b>{{ number_format($paidCoursePurchases) }}</b></span>
      <span>فروش ۳۰ روز اخیر <b>{{ number_format($revenueLast30Days) }} تومان</b></span>
    </div>
    @if($paidCoursePurchases === 0)
      <p class="adm-sales-empty">هنوز خرید دوره‌ای با پرداخت تأییدشده ثبت نشده است.</p>
    @endif
  </aside>
</section>


<section class="adm-stack" aria-label="مرکز اقدام امروز">
  <div class="adm-pagehead">
    <div><h2>الان چه کارهایی باید انجام بدهم؟</h2><p>موارد نیازمند پیگیری و نزدیک‌ترین جلسات را از همین‌جا دنبال کن.</p></div>
  </div>
  <div class="adm-work-queue">
    @can('users.manage')
      @if(($pendingPaymentReviewCount ?? 0) > 0)
        <a class="adm-work-item" data-tone="shop" href="{{ route('admin.payments.index', ['status' => 'pending_review']) }}"><span><x-admin.icon name="wallet"/></span><div><b>رسیدهای پرداخت نیازمند بررسی</b><small>قبل از تأیید، اطلاعات واریز را بررسی کن</small></div><strong>{{ number_format($pendingPaymentReviewCount) }}</strong></a>
      @endif
    @endcan
    @if(($recentEnrollmentCount ?? 0) > 0)
      <a class="adm-work-item" data-tone="users" href="{{ route('admin.enrollments.index') }}"><span><x-admin.icon name="users"/></span><div><b>ثبت‌نام‌های جدید در ۷ روز اخیر</b><small>آخرین درخواست‌ها و وضعیت دسترسی را بررسی کن</small></div><strong>{{ number_format($recentEnrollmentCount) }}</strong></a>
    @endif
    @canany(['classes.manage', 'classes.manage.assigned', 'classes.view.assigned'])
      @if(($upcomingSessions ?? collect())->isNotEmpty())
        <div class="adm-work-item" data-tone="edu"><span><x-admin.icon name="calendar"/></span><div><b>جلسات پیش‌رو</b><small>جلسات برنامه‌ریزی‌شده تا ۷ روز آینده</small></div><strong>{{ number_format($upcomingSessions->count()) }}</strong></div>
      @endif
    @endcanany
    @if(($pendingPaymentReviewCount ?? 0) === 0 && ($recentEnrollmentCount ?? 0) === 0 && ($upcomingSessions ?? collect())->isEmpty())
      <div class="adm-queue-empty"><x-admin.icon name="check"/><b>مورد فوری تازه‌ای ثبت نشده است</b><span>صفحه‌های تخصصی را برای پیگیری سایر فعالیت‌ها باز کن.</span></div>
    @endif
  </div>
  @if(($upcomingSessions ?? collect())->isNotEmpty())
    <div class="adm-action-grid">
      @foreach($upcomingSessions as $session)
        <a class="adm-action-tile" href="{{ route('admin.classes.sessions.index', $session->classroom_id) }}">
          <span><x-admin.icon name="calendar"/></span>
          <div><b>{{ $session->title }} · {{ $session->classroom?->name }}</b><small>{{ $session->starts_at?->translatedFormat('l j F، H:i') }}</small></div>
          <x-admin.icon name="chev"/>
        </a>
      @endforeach
    </div>
  @endif
  @if(($recentEnrollments ?? collect())->isNotEmpty())
    <div class="adm-action-grid">
      @foreach($recentEnrollments as $enrollment)
        <a class="adm-action-tile" href="{{ route('admin.enrollments.index', ['q' => $enrollment->student?->name]) }}">
          <span><x-admin.icon name="user"/></span>
          <div><b>{{ $enrollment->student?->name ?? 'دانش‌آموز' }}</b><small>{{ $enrollment->course?->title ?? 'دوره' }} · ثبت {{ $enrollment->created_at?->diffForHumans() }}</small></div>
          <x-admin.icon name="chev"/>
        </a>
      @endforeach
    </div>
  @endif
</section>


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
<div class="adm-stats">
  <x-admin.stat tone="users" icon="user" label="دانش‌آموزان فعال" :value="number_format($stats['students'] ?? 0)" hint="در محدوده دسترسی" :href="route('admin.students.index')"/>
  <x-admin.stat tone="edu" icon="book" label="دوره‌های منتشرشده" :value="number_format($stats['courses'] ?? 0)" hint="قابل مشاهده برای دانش‌آموزان" :href="route('admin.courses.index')"/>
  <x-admin.stat tone="edu" icon="cls" label="گروه‌های آموزشی فعال" :value="number_format($stats['classes'] ?? 0)" hint="کلاس‌های در حال برگزاری" :href="route('admin.classes.index')"/>
  <x-admin.stat tone="track" icon="file" label="پاسخ‌های منتظر بررسی" :value="number_format($pendingHomework)" hint="نیازمند اقدام مدرس" :href="route('admin.homework.index')"/>
  <x-admin.stat tone="users" icon="users" label="مدرسان فعال" :value="number_format($stats['teachers'] ?? 0)" hint="حساب‌های فعال" :href="route('admin.teachers.index')"/>
  <x-admin.stat tone="shop" icon="chart" label="درآمد تأییدشده ۳۰ روز اخیر" :value="number_format($revenueLast30Days)" hint="تومان · پرداخت‌های موفق دوره"/>
</div>


@endsection
