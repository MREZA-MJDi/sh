@extends('admin.layouts.app')
@section('title', 'داشبورد مدیریت')

@section('content')
@php
  $stats = $stats ?? [];
  $todayClasses = $todayClasses ?? collect();
  $recentMessages = $recentMessages ?? collect();
  $pendingConsultations = $pendingConsultations ?? collect();
  $recentAnnouncements = $recentAnnouncements ?? collect();
  $weeklyAttendance = $weeklyAttendance ?? collect();
@endphp

<x-admin.page-head title="داشبورد مدیریت" :desc="'سلام، '.(auth()->user()?->name ?? 'مدیر سامانه').' · نمای زنده وضعیت آکادمی'" icon="home"/>

<x-admin.panel title="میانبرهای سریع" desc="دسترسی مستقیم به بخش‌های اصلی عملیات روزانه" data-tone="edu">
  <div class="adm-actions">
    @can('courses.manage')
      <x-admin.btn icon="book" :href="route('admin.courses.index')">مدیریت دوره‌ها</x-admin.btn>
    @endcan
    @can('classes.manage')
      <x-admin.btn variant="line" icon="cls" :href="route('admin.classes.index')">کلاس‌ها و جلسات</x-admin.btn>
    @endcan
    @can('integrations.manage')
      <x-admin.btn variant="line" icon="video" :href="route('admin.spotplayer.index')">SpotPlayer</x-admin.btn>
      <x-admin.btn variant="line" icon="exam" :href="route('admin.quiz24.index')">Quiz24</x-admin.btn>
    @endcan
    @can('users.manage')
      <x-admin.btn variant="line" icon="users" :href="route('admin.students.index')">دانش‌آموزان</x-admin.btn>
    @endcan
    @can('homework.manage.all')
      <x-admin.btn variant="line" icon="file" :href="route('admin.homework.index')">تکالیف</x-admin.btn>
    @endcan
    @can('messages.view.all')
      <x-admin.btn variant="line" icon="msg" :href="route('admin.messages.index')">کارتابل پیام‌ها</x-admin.btn>
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

<div class="adm-split">
  <x-admin.panel title="گروه‌های آموزشی فعال" desc="نمای واقعی کلاس‌های فعال و مسئول هر گروه" flush data-tone="edu">
    <div class="adm-table-wrap"><table class="adm-table">
      <thead><tr><th>کلاس / گروه</th><th>دوره</th><th>مدرس</th><th>دانش‌آموز</th><th></th></tr></thead>
      <tbody>
        @forelse($todayClasses as $classroom)
          <tr>
            <td><b>{{ $classroom->name }}</b><small>{{ $classroom->schedule['days'] ?? 'برنامه ثبت‌نشده' }} · {{ $classroom->schedule['time'] ?? '—' }}</small></td>
            <td>{{ $classroom->course?->title ?? 'کلاس مستقل' }}</td>
            <td>{{ $classroom->teacher?->name ?? '—' }}</td>
            <td class="adm-num">{{ number_format($classroom->students_count) }}</td>
            <td><x-admin.btn size="sm" variant="line" :href="route('admin.classes.sessions.index', $classroom)">جلسات</x-admin.btn></td>
          </tr>
        @empty
          <tr><td colspan="5">گروه آموزشی فعالی برای نمایش وجود ندارد.</td></tr>
        @endforelse
      </tbody>
    </table></div>
  </x-admin.panel>

  <x-admin.panel title="نیاز به اقدام" desc="مواردی که به پیگیری مدیر نیاز دارند">
    <div class="adm-todo">
      @can('homework.grade')
        <a href="{{ route('admin.homework.index') }}" data-tone="track"><span class="adm-stat-ico"><x-admin.icon name="file"/></span>پاسخ‌های تکلیف در انتظار بررسی<strong class="adm-num">{{ number_format($stats['pending_homework'] ?? 0) }}</strong></a>
      @endcan
      @can('messages.view.all')
        <a href="{{ route('admin.messages.index') }}" data-tone="comm"><span class="adm-stat-ico"><x-admin.icon name="msg"/></span>گفت‌وگوهای باز<strong class="adm-num">{{ number_format($recentMessages->count()) }}</strong></a>
      @endcan
      @can('consultations.manage')
        <a href="{{ route('admin.consultations.index') }}" data-tone="comm"><span class="adm-stat-ico"><x-admin.icon name="chat"/></span>درخواست‌های مشاوره جدید<strong class="adm-num">{{ number_format($pendingConsultations->count()) }}</strong></a>
      @endcan
      @can('announcements.manage')
        <a href="{{ route('admin.announcements.index') }}" data-tone="edu"><span class="adm-stat-ico"><x-admin.icon name="bell"/></span>اطلاعیه‌های منتشرشده<strong class="adm-num">{{ number_format($recentAnnouncements->count()) }}</strong></a>
      @endcan
    </div>
  </x-admin.panel>
</div>

<div class="adm-split is-even">
  <x-admin.panel title="روند حضور هفتگی" desc="درصد حضور از داده‌های واقعی ثبت‌شده">
    <div class="adm-bars" data-tone="home">
      @forelse($weeklyAttendance as $day)
        <div><span class="adm-bar" style="height:{{ max(4, (int) $day['rate']) }}%"><b class="adm-num">{{ $day['rate'] }}٪</b></span>{{ $day['label'] }}</div>
      @empty
        <p class="adm-muted">هنوز داده حضور برای نمایش وجود ندارد.</p>
      @endforelse
    </div>
  </x-admin.panel>

  <x-admin.panel title="پیام‌های اخیر" desc="گفت‌وگوهای باز در کارتابل">
    @forelse($recentMessages as $message)
      <div class="adm-session">
        <div class="adm-grow"><b>{{ $message->subject }}</b><div class="adm-muted">{{ $message->sender?->name ?? 'کاربر' }} · {{ \Illuminate\Support\Str::limit(strip_tags($message->body), 90) }}</div></div>
        <x-admin.btn size="sm" variant="line" :href="route('admin.messages.index', ['message' => $message->id])">مشاهده</x-admin.btn>
      </div>
    @empty
      <p class="adm-muted">گفت‌وگوی بازی برای نمایش وجود ندارد.</p>
    @endforelse
  </x-admin.panel>
</div>

<div class="adm-split is-even">
  <x-admin.panel title="درخواست‌های مشاوره" desc="درخواست‌های در انتظار بررسی">
    @forelse($pendingConsultations as $consultation)
      <div class="adm-session">
        <div class="adm-grow"><b>{{ $consultation->topic }}</b><div class="adm-muted">{{ $consultation->requester?->name ?? 'کاربر' }} · {{ $consultation->student?->name ?? '—' }}</div></div>
        <x-admin.btn size="sm" variant="line" :href="route('admin.consultations.index')">بررسی</x-admin.btn>
      </div>
    @empty
      <p class="adm-muted">درخواست مشاوره در انتظاری وجود ندارد.</p>
    @endforelse
  </x-admin.panel>
  <x-admin.panel title="اطلاعیه‌های منتشرشده" desc="آخرین اطلاعیه‌های قابل مشاهده">
    @forelse($recentAnnouncements as $announcement)
      <div class="adm-session"><div class="adm-grow"><b>{{ $announcement->title }}</b><div class="adm-muted">{{ $announcement->published_at?->format('Y/m/d H:i') }}</div></div></div>
    @empty
      <p class="adm-muted">اطلاعیه‌ای منتشر نشده است.</p>
    @endforelse
  </x-admin.panel>
</div>
@endsection
