@extends('admin.layouts.app')
@section('title', 'داشبورد')

@section('content')
@php
  // Placeholder data — replace with controller variables.
  $months = $months ?? [['مهر', 40, 96], ['آبان', 55, 132], ['آذر', 48, 115], ['دی', 70, 168], ['بهمن', 82, 197], ['اسفند', 65, 156]];
  $today = $today ?? [
    ['16:00', 'ریاضی نهم — گروه الف', 'دکتر محمدی', 'live'],
    ['17:30', 'فیزیک دهم — گروه ب', 'خانم صادقی', 'next'],
    ['19:00', 'شیمی یازدهم — گروه ج', 'آقای کریمی', 'next'],
  ];
  $recent = $recent ?? [
    ['سارا احمدی', 'ریاضی نهم — پلن پیشرفته', 'ok', 'پرداخت‌شده'],
    ['علی رضایی', 'فیزیک دهم — پلن پایه', 'warn', 'در انتظار پرداخت'],
    ['نگار کریمی', 'زیست یازدهم — فقط کلاس زنده', 'ok', 'پرداخت‌شده'],
    ['رضا مرادی', 'ریاضی دهم — پلن ویژه', 'ok', 'پرداخت‌شده'],
  ];
@endphp

<x-admin.page-head title="سلام، مدیر سامانه" desc="دوشنبه ۲۵ آبان ۱۴۰۵ — خلاصه وضعیت آکادمی" icon="home">
  <x-admin.btn variant="line" icon="user" :href="url('admin/students')">دانش‌آموز جدید</x-admin.btn>
  <x-admin.btn variant="line" icon="live" :href="url('admin/online-classes')">کلاس آنلاین جدید</x-admin.btn>
  <x-admin.btn icon="book" :href="url('admin/courses')">دوره جدید</x-admin.btn>
</x-admin.page-head>

<div class="adm-stats">
  <x-admin.stat tone="users" icon="user" label="دانش‌آموزان" value="1,284" hint="۳۲+ این ماه"/>
  <x-admin.stat tone="shop" icon="lock" label="ثبت‌نام‌های فعال" value="1,046" hint="۳۸ مورد در حال انقضا"/>
  <x-admin.stat tone="edu" icon="live" label="کلاس‌های آنلاین امروز" value="3" hint="۱ در حال برگزاری"/>
  <x-admin.stat tone="shop" icon="card" label="پرداخت در انتظار" value="7" hint="نیازمند بررسی"/>
</div>

<div class="adm-split">
  <x-admin.panel title="کلاس‌های آنلاین امروز" desc="ورود به کلاس برای دانش‌آموزان در همین ساعت‌ها فعال می‌شود" flush data-tone="edu">
    <x-slot:actions><x-admin.btn variant="line" size="sm" :href="url('admin/online-classes')">همه کلاس‌ها</x-admin.btn></x-slot:actions>
    <div data-tone="edu">
      @foreach ($today as [$time, $title, $teacher, $state])
        <div class="adm-session {{ $state === 'live' ? 'is-live' : '' }}">
          <div class="adm-session-time adm-num">{{ $time }}<small>{{ $state === 'live' ? 'زنده' : 'امروز' }}</small></div>
          <div class="adm-grow"><b>{{ $title }}</b><div class="adm-muted">{{ $teacher }}</div></div>
          @if ($state === 'live')<x-admin.badge tone="bad">در حال برگزاری</x-admin.badge>@else<x-admin.badge tone="mute">منتظر شروع</x-admin.badge>@endif
          <x-admin.btn variant="soft" size="sm" icon="link" :href="url('admin/online-classes')">لینک ورود</x-admin.btn>
        </div>
      @endforeach
    </div>
  </x-admin.panel>

  <x-admin.panel title="نیاز به اقدام" flush>
    <div class="adm-todo">
      <a href="{{ url('admin/enrollments') }}" data-tone="shop"><span class="adm-stat-ico"><x-admin.icon name="lock"/></span>مجوزهای در حال انقضا (۳۰ روز)<strong class="adm-num">38</strong></a>
      <a href="{{ url('admin/payments') }}" data-tone="shop"><span class="adm-stat-ico"><x-admin.icon name="card"/></span>پرداخت‌های منتظر تأیید<strong class="adm-num">7</strong></a>
      <a href="{{ url('admin/parent-messages') }}" data-tone="comm"><span class="adm-stat-ico"><x-admin.icon name="msg"/></span>پیام‌های بی‌پاسخ اولیا<strong class="adm-num">5</strong></a>
      <a href="{{ url('admin/assignments') }}" data-tone="track"><span class="adm-stat-ico"><x-admin.icon name="file"/></span>تکالیف منتظر بررسی<strong class="adm-num">12</strong></a>
      <a href="{{ url('admin/consultations') }}" data-tone="comm"><span class="adm-stat-ico"><x-admin.icon name="chat"/></span>درخواست مشاوره جدید<strong class="adm-num">2</strong></a>
      <a href="{{ url('admin/students') }}" data-tone="users"><span class="adm-stat-ico"><x-admin.icon name="family"/></span>دانش‌آموز بدون ولی<strong class="adm-num">9</strong></a>
    </div>
  </x-admin.panel>
</div>

<div class="adm-split is-even">
  <x-admin.panel title="ثبت‌نام ماهانه" desc="۶ ماه اخیر">
    <div class="adm-bars" data-tone="home">
      @foreach ($months as [$m, $h, $n])
        <div><span class="adm-bar" style="height:{{ $h }}%"><b class="adm-num">{{ $n }}</b></span>{{ $m }}</div>
      @endforeach
    </div>
  </x-admin.panel>

  <x-admin.panel title="آخرین ثبت‌نام‌ها" flush>
    <x-slot:actions><x-admin.btn variant="line" size="sm" :href="url('admin/payments')">همه</x-admin.btn></x-slot:actions>
    <div class="adm-table-wrap"><table class="adm-table">
      <thead><tr><th>دانش‌آموز</th><th>دوره و پلن</th><th>وضعیت</th></tr></thead>
      <tbody>
        @foreach ($recent as [$name, $item, $tone, $label])
          <tr><td><x-admin.person :name="$name"/></td><td>{{ $item }}</td><td><x-admin.badge :tone="$tone">{{ $label }}</x-admin.badge></td></tr>
        @endforeach
      </tbody>
    </table></div>
  </x-admin.panel>
</div>
@endsection
