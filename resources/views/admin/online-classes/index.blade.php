@extends('admin.layouts.app')
@section('title', 'جلسات آنلاین')

@section('content')
@php
  $statusLabels = [
    'scheduled' => ['info', 'برنامه‌ریزی‌شده'],
    'completed' => ['ok', 'برگزارشده'],
    'cancelled' => ['warn', 'لغوشده'],
  ];
@endphp

<x-admin.page-head tone="edu" icon="live" title="کلاس‌های آنلاین" desc="جلسه‌های آنلاین را پیگیری کن، زمان و وضعیت ورود را ببین و مستقیم به برنامه کلاس برو.">
  <x-admin.btn variant="line" icon="cls" :href="route('admin.classes.index')">مدیریت گروه‌های آموزشی</x-admin.btn>
</x-admin.page-head>

<section class="adm-online-intro" data-tone="edu">
  <div>
    <h2>برنامه کلاس‌ها، بدون جست‌وجو بین صفحه‌ها</h2>
    <p>جلسه‌های نزدیک‌تر بالاتر نمایش داده می‌شوند. اگر لینک ورود هنوز آماده نیست، از صفحه همان کلاس می‌توانی وضعیت اتاق را پیگیری کنی.</p>
  </div>
  <div class="adm-online-now">
    <i aria-hidden="true"></i>
    <div><b>{{ number_format($stats['upcoming']) }} جلسه پیش‌رو</b><small>جلسه‌های زمان‌بندی‌شده آینده</small></div>
  </div>
</section>

<div class="adm-stats">
  <x-admin.stat tone="edu" icon="live" label="جلسات امروز" :value="number_format($stats['today'])" hint="برنامه امروز آکادمی"/>
  <x-admin.stat tone="edu" icon="cal" label="جلسات این هفته" :value="number_format($stats['week'])" hint="از شروع تا پایان هفته"/>
  <x-admin.stat tone="edu" icon="clock" label="جلسات پیش‌رو" :value="number_format($stats['upcoming'])" hint="نیازمند آمادگی قبل از شروع"/>
  <x-admin.stat tone="shop" icon="link" label="اتاق‌های نیازمند اتصال" :value="number_format($stats['waiting'])" hint="زمان جلسه ثبت شده؛ لینک هنوز آماده نیست"/>
</div>

<x-admin.panel title="برنامه جلسه‌ها" desc="برای هر جلسه، زمان، کلاس، مدرس و وضعیت ورود را یک‌جا ببین." flush data-tone="edu">
  <form method="GET" action="{{ route('admin.online-classes.index') }}" class="adm-online-filters">
    <label class="adm-video-search"><x-admin.icon name="search"/><input class="adm-input" type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="عنوان جلسه، نام کلاس، دوره یا مدرس" aria-label="جستجوی جلسات آنلاین"></label>
    <select class="adm-select" name="status" aria-label="وضعیت جلسه">
      <option value="">همه وضعیت‌ها</option>
      @foreach($statusLabels as $key => $status)
        <option value="{{ $key }}" @selected(($filters['status'] ?? '') === $key)>{{ $status[1] }}</option>
      @endforeach
    </select>
    <button class="adm-btn adm-btn-primary adm-btn-sm" type="submit">اعمال فیلتر</button>
    @if(collect($filters)->filter(fn ($value) => filled($value))->isNotEmpty())
      <a class="adm-btn adm-btn-line adm-btn-sm" href="{{ route('admin.online-classes.index') }}">پاک‌کردن</a>
    @endif
  </form>

  <div class="adm-online-grid">
    @forelse($sessions as $session)
      @php
        $classroom = $session->classroom;
        $status = $statusLabels[$session->status] ?? ['mute', $session->status];
        $meetingLink = $session->meeting_url ?: $classroom?->meeting_url;
        $sessionIsUpcoming = $session->status === 'scheduled' && $session->starts_at?->isFuture();
      @endphp
      <article class="adm-online-card">
        <div class="adm-online-card-head">
          <div>
            <h3>{{ $session->title }}</h3>
            <p>{{ $classroom?->course?->title ?? 'دوره مستقل' }} · {{ $classroom?->name ?? 'کلاس حذف‌شده' }}</p>
          </div>
          <x-admin.badge :tone="$status[0]">{{ $status[1] }}</x-admin.badge>
        </div>

        <div class="adm-online-time">
          <x-admin.icon name="cal"/>
          <div>
            <b>{{ $session->starts_at ? \App\Support\PersianDate::format($session->starts_at, 'l، d F · H:i') : 'زمان مشخص نشده' }}</b>
            <small>{{ $session->ends_at ? 'پایان: '.\App\Support\PersianDate::format($session->ends_at, 'H:i') : 'زمان پایان ثبت نشده' }}</small>
          </div>
        </div>

        <div class="adm-online-meta">
          <span><x-admin.icon name="teacher"/>{{ $classroom?->teacher?->name ?? 'مدرس تعیین نشده' }}</span>
          <span><x-admin.icon name="users"/>{{ number_format($session->attendances_count) }} حضور ثبت‌شده</span>
          @if($sessionIsUpcoming)
            <span><x-admin.icon name="clock"/>شروع {{ $session->starts_at?->diffForHumans() }}</span>
          @endif
        </div>

        <div class="adm-online-card-foot">
          @if(filled($meetingLink))
            <a class="adm-online-link" href="{{ $meetingLink }}" target="_blank" rel="noopener noreferrer"><x-admin.icon name="link"/>باز کردن لینک جلسه</a>
          @else
            <span class="adm-muted">لینک ورود هنوز آماده نیست</span>
          @endif
          @if($classroom)
            <x-admin.btn size="sm" variant="line" icon="calendar" :href="route('admin.classes.sessions.index', $classroom)">برنامه کلاس</x-admin.btn>
          @endif
        </div>
      </article>
    @empty
      <div class="adm-online-empty">
        <x-admin.icon name="live"/>
        <b>{{ collect($filters)->filter(fn ($value) => filled($value))->isNotEmpty() ? 'جلسه‌ای با این فیلتر پیدا نشد' : 'هنوز جلسه آنلاینی برنامه‌ریزی نشده است' }}</b>
        <p>{{ collect($filters)->filter(fn ($value) => filled($value))->isNotEmpty() ? 'فیلترها را پاک کن تا همه جلسه‌ها را ببینی.' : 'برای شروع، یک گروه آموزشی باز کن و از تقویم آن، جلسه آنلاین جدیدی برنامه‌ریزی کن.' }}</p>
        <x-admin.btn size="sm" icon="cls" :href="route('admin.classes.index')">رفتن به گروه‌های آموزشی</x-admin.btn>
      </div>
    @endforelse
  </div>

  @if($sessions->hasPages())
    <x-slot:footer>{{ $sessions->links() }}</x-slot:footer>
  @endif
</x-admin.panel>
@endsection
