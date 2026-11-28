@extends('admin.layouts.app')
@section('title', 'مرکز ارتباطات')

@section('content')
@php
  $typeLabels = [
    'messages' => 'گفت‌وگوها',
    'contacts' => 'فرم تماس سایت',
    'consultations' => 'مشاوره‌ها',
    'meetings' => 'جلسات اولیا',
  ];
  $statusLabels = [
    'open' => 'باز', 'answered' => 'پاسخ‌داده‌شده', 'read' => 'خوانده‌شده',
    'new' => 'جدید', 'in_progress' => 'در حال پیگیری', 'resolved' => 'رسیدگی‌شده', 'spam' => 'هرزنامه',
    'pending' => 'در انتظار', 'scheduled' => 'زمان‌بندی‌شده', 'done' => 'انجام‌شده',
    'requested' => 'درخواست‌شده', 'proposed' => 'پیشنهادشده', 'completed' => 'تکمیل‌شده',
    'declined' => 'ردشده', 'cancelled' => 'لغوشده',
  ];
  $typeIcons = ['messages' => 'msg', 'contacts' => 'msg', 'consultations' => 'chat', 'meetings' => 'cal'];
@endphp

<x-admin.page-head tone="comm" icon="msg" title="مرکز ارتباطات" desc="پیام‌های کاربران، فرم تماس سایت، درخواست‌های مشاوره و جلسات اولیا را از یک نقطه پیگیری کن.">
</x-admin.page-head>

<div class="adm-stats">
  @foreach($typeLabels as $type => $label)
    <x-admin.stat tone="comm" icon="{{ $typeIcons[$type] }}" :label="$label" :value="number_format((int) ($counts[$type] ?? 0))"/>
  @endforeach
</div>

<x-admin.panel title="صف پیگیری ارتباطات" icon="msg" flush data-tone="comm">
  <form method="GET" action="{{ route('admin.communication-center.index') }}" class="adm-toolbar">
    <input class="adm-input" type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="جستجوی موضوع، نام، شماره تماس یا متن پیام…" aria-label="جستجوی پیام‌ها" style="min-width:0;flex:1 1 240px">
    <select class="adm-select" name="type" aria-label="دسته پیام">
      <option value="">همه دسته‌ها</option>
      @foreach($typeLabels as $type => $label)
        <option value="{{ $type }}" @selected(($filters['type'] ?? '') === $type)>{{ $label }}</option>
      @endforeach
    </select>
    <select class="adm-select" name="attention" aria-label="نیاز به پیگیری">
      <option value="">همه وضعیت‌ها</option>
      <option value="needed" @selected(($filters['attention'] ?? '') === 'needed')>نیازمند پیگیری</option>
    </select>
    <div class="adm-toolbar-end">
      <x-admin.btn type="submit" size="sm">اعمال فیلتر</x-admin.btn>
      <x-admin.btn :href="route('admin.communication-center.index')" variant="line" size="sm">پاک‌کردن</x-admin.btn>
    </div>
  </form>

  <div class="adm-communication-list">
    @forelse($items as $item)
      <a class="adm-communication-item" href="{{ $item['url'] }}">
        <span class="adm-communication-icon" data-type="{{ $item['type'] }}"><x-admin.icon :name="$typeIcons[$item['type']] ?? 'msg'"/></span>
        <span class="adm-communication-main">
          <span class="adm-communication-topline"><strong>{{ $item['title'] }}</strong><small>{{ \App\Support\PersianDate::format($item['created_at'], 'Y/m/d H:i') }}</small></span>
          <span class="adm-communication-meta">{{ $item['label'] }} · {{ $item['sender'] }} @if($item['context']) · {{ $item['context'] }} @endif @if(!empty($item['owner'])) · مسئول: {{ $item['owner'] }} @endif</span>
          <span class="adm-communication-direction" data-direction="{{ $item['direction'] }}">
            <x-admin.icon name="msg"/>
            {{ $item['direction_label'] }}
          </span>
          <span class="adm-communication-summary">{{ $item['summary'] }}</span>
        </span>
        <x-admin.badge tone="{{ in_array($item['status'], ['resolved', 'done', 'completed', 'answered', 'read'], true) ? 'ok' : (in_array($item['status'], ['spam', 'declined', 'cancelled'], true) ? 'mute' : 'warn') }}">{{ $statusLabels[$item['status']] ?? $item['status'] }}</x-admin.badge>
      </a>
    @empty
      <x-admin.empty icon="msg" text="پیامی با این فیلتر پیدا نشد."/>
    @endforelse
  </div>
  <x-slot:footer><x-admin.pager :current="$items->currentPage()" :last="$items->lastPage()" :total="$items->total()" :per="$items->perPage()"/></x-slot:footer>
</x-admin.panel>
@endsection
