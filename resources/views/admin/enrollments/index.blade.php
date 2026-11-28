@extends('admin.layouts.app')
@section('title', 'ثبت‌نام‌ها و مجوزها')
@section('content')
@php
  $statusLabels = ['active' => ['ok', 'فعال'], 'pending_payment' => ['warn', 'در انتظار پرداخت'], 'suspended' => ['bad', 'تعلیق‌شده'], 'revoked' => ['mute', 'لغوشده']];
  $sources = ['classroom' => 'عضویت کلاس', 'free' => 'رایگان', 'payment' => 'پرداخت تأییدشده', 'manual' => 'اعطای دستی'];
  $features = ['recorded_video' => 'ویدیوی ضبط‌شده', 'live_class' => 'کلاس آنلاین', 'resources' => 'منابع', 'assignments' => 'تکالیف', 'exams' => 'آزمون', 'projects' => 'پروژه'];
  $courseOptions = $courses->pluck('title', 'id')->all();
@endphp
<x-admin.page-head tone="shop" icon="lock" title="ثبت‌نام‌ها و مجوزها" desc="عضویت آموزشی و مجوز دسترسی مستقل‌اند؛ وضعیت واقعی دسترسی هر دانش‌آموز در این صفحه مدیریت می‌شود">
  <x-admin.btn icon="plus" data-modal-open="enr-modal">ثبت‌نام دستی</x-admin.btn>
</x-admin.page-head>
<section class="adm-enrollment-guide" data-tone="shop">
  <div><span>راهنمای دسترسی آموزشی</span><b>ثبت‌نام، مجوز ورود به محتوای دوره است؛ پرداخت فقط یکی از راه‌های ایجاد آن است.</b><small>برای بررسی رسید و تأیید پرداخت به صفحه پرداخت‌ها برو؛ اینجا وضعیت دسترسی و امکانات فعال دانش‌آموز را کنترل کن.</small></div>
  <x-admin.btn size="sm" variant="line" icon="card" :href="route('admin.payments.index')">رفتن به پرداخت‌ها</x-admin.btn>
</section>
<div class="adm-stats">
  <x-admin.stat tone="shop" icon="check" label="دسترسی فعال" :value="number_format($stats['active'])"/>
  <x-admin.stat tone="shop" icon="clock" label="در حال انقضا (۳۰ روز)" :value="number_format($stats['expiring'])"/>
  <x-admin.stat tone="shop" icon="refresh" label="منقضی‌شده" :value="number_format($stats['expired'])"/>
  <x-admin.stat tone="shop" icon="alert" label="تعلیق / منتظر پرداخت" :value="number_format($stats['pending'])"/>
</div>
<x-admin.panel flush data-tone="shop">
  <form method="GET" class="adm-toolbar">
    <input class="adm-input" type="search" name="q" value="{{ request('q') }}" placeholder="جستجوی دانش‌آموز یا دوره…">
    <select class="adm-select" name="course_id"><option value="">همه دوره‌ها</option>@foreach($courses as $course)<option value="{{ $course->id }}" @selected((string) request('course_id') === (string) $course->id)>{{ $course->title }}</option>@endforeach</select>
    <select class="adm-select" name="status"><option value="">همه وضعیت‌ها</option>@foreach($statusLabels as $key => $label)<option value="{{ $key }}" @selected(request('status') === $key)>{{ $label[1] }}</option>@endforeach</select>
    <button class="adm-btn adm-btn-line adm-btn-sm" type="submit">اعمال فیلتر</button>
  </form>
  <div class="adm-table-wrap"><table class="adm-table">
    <thead><tr><th>دانش‌آموز</th><th>دوره</th><th>امکانات مجاز</th><th>منبع</th><th>اعتبار تا</th><th>وضعیت</th><th></th></tr></thead>
    <tbody>
      @forelse($enrollments as $enrollment)
        @php
          $expired = $enrollment->expires_at && $enrollment->expires_at->isPast();
          $status = $expired ? ['mute', 'منقضی'] : ($statusLabels[$enrollment->status] ?? ['mute', $enrollment->status]);
          $enabled = collect($enrollment->course?->learning_features ?? [])->filter(fn ($value) => $value)->keys()->map(fn ($key) => $features[$key] ?? $key);
        @endphp
        <tr>
          <td><b>{{ $enrollment->student?->name ?? 'حساب حذف‌شده' }}</b><small>{{ $enrollment->student?->phone ?: $enrollment->student?->email ?: '—' }}</small></td>
          <td><b>{{ $enrollment->course?->title ?? 'دوره حذف‌شده' }}</b><small>{{ number_format((int) ($enrollment->course?->price ?? 0)) }} تومان</small></td>
          <td>{{ $enabled->isNotEmpty() ? $enabled->join('، ') : 'طبق تنظیمات دوره' }}</td>
          <td>{{ $sources[$enrollment->source] ?? $enrollment->source }}<small>#{{ $enrollment->id }}</small></td>
          <td class="adm-num is-nowrap">{{ ($enrollment->expires_at ? \App\Support\PersianDate::format($enrollment->expires_at, 'Y/m/d H:i') : 'بدون انقضا') }}</td>
          <td><x-admin.badge :tone="$status[0]">{{ $status[1] }}</x-admin.badge></td>
          <td>
            @if($enrollment->status !== 'revoked')
              <form method="POST" action="{{ route('admin.enrollments.revoke', $enrollment) }}" onsubmit="return confirm('دسترسی این دانش‌آموز لغو شود؟ سابقه ثبت‌نام حفظ می‌شود.')">
                @csrf @method('PATCH')
                <button class="adm-btn adm-btn-bad adm-btn-sm" type="submit">لغو دسترسی</button>
              </form>
            @else <span class="adm-muted">در سوابق</span> @endif
          </td>
        </tr>
      @empty
        <tr><td colspan="7">هنوز ثبت‌نامی در سامانه ثبت نشده است؛ اطلاعات نمونه نمایش داده نمی‌شود.</td></tr>
      @endforelse
    </tbody>
  </table></div>
  <x-slot:footer>{{ $enrollments->links() }}</x-slot:footer>
</x-admin.panel>
@endsection
@push('modals')
<x-admin.modal :demo="false" id="enr-modal" title="اعطای دستی دسترسی دوره" size="lg" :action="route('admin.enrollments.store')">
  <x-admin.legend title="دانش‌آموز و دوره"/>
  <x-admin.student-search :selected="$selectedStudent"/>
  <x-admin.select label="دوره منتشرشده" name="course_id" :options="$courseOptions" required/>
  <x-admin.input label="اعتبار تا" name="expires_at" type="datetime-local" hint="خالی = بدون انقضا"/>
  <p class="adm-muted wide">این عملیات فقط مجوز دستی ثبت می‌کند؛ پرداختی ساخته نمی‌شود و سوابق پرداخت تأییدشده بازنویسی نخواهد شد.</p>
</x-admin.modal>
@endpush
