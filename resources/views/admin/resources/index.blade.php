@extends('admin.layouts.app')
@section('title', 'منابع آموزشی')

@section('content')
@php
  $courseOptions = $courses->pluck('title', 'id')->all();
  $classroomOptions = $classrooms->pluck('name', 'id')->all();
  $kindOptions = ['pdf' => 'PDF', 'book' => 'کتاب / جزوه', 'other' => 'سایر'];
  $visibilityOptions = $teacherView
    ? ['authenticated' => 'کاربران واردشده', 'enrolled' => 'ثبت‌نام‌شده‌ها']
    : ['public' => 'عمومی', 'authenticated' => 'کاربران واردشده', 'enrolled' => 'ثبت‌نام‌شده‌ها'];
  $statusOptions = ['draft' => 'پیش‌نویس', 'published' => 'منتشرشده', 'archived' => 'بایگانی'];
@endphp

<x-admin.page-head tone="shop" icon="lib" title="منابع و محصولات آموزشی" desc="جزوه‌ها و فایل‌های دوره یا کلاس با سطح دسترسی و ثبت رویداد دریافت">
  <x-admin.btn icon="plus" data-modal-open="resource-modal">افزودن منبع</x-admin.btn>
</x-admin.page-head>

<section class="adm-resource-guide" data-tone="shop">
  <div><span>انتشار و دسترسی فایل</span><b>قبل از انتشار، مخاطب و سطح دسترسی هر منبع را مشخص کن.</b><small>{{ $teacherView ? 'منابع این صفحه فقط برای دوره‌ها و کلاس‌های تخصیص‌یافته شماست.' : 'منبع می‌تواند عمومی، مخصوص کاربران واردشده یا محدود به ثبت‌نام‌شده‌ها باشد؛ رویدادهای دریافت هم ثبت می‌شوند.' }}</small></div>
</section>

<x-admin.panel flush>
  <div class="adm-grid adm-cols-2">
    @forelse($resources as $resource)
      @php
        $fill = [
          'title' => $resource->title,
          'description' => $resource->description,
          'kind' => $resource->kind,
          'course_id' => (string) ($resource->course_id ?? ''),
          'classroom_id' => (string) ($resource->classroom_id ?? ''),
          'is_free' => (bool) $resource->is_free,
          'price' => $resource->price,
          'visibility' => $resource->visibility,
          'release_at' => $resource->release_at?->format('Y-m-d\\TH:i'),
          'downloadable' => (bool) $resource->downloadable,
          'status' => $resource->status,
        ];
      @endphp
      <article class="adm-card">
        <div class="adm-card-body">
          <div class="adm-card-title">
            <div><h3>{{ $resource->title }}</h3><p>{{ $resource->uploader?->name ?? 'ثبت‌کننده نامشخص' }}</p></div>
            <x-admin.badge :tone="$resource->status === 'published' ? 'ok' : 'warn'">{{ $statusOptions[$resource->status] ?? $resource->status }}</x-admin.badge>
          </div>
          <div class="adm-meta">
            <span><x-admin.icon name="file"/>{{ $kindOptions[$resource->kind] ?? $resource->kind }}</span>
            <span><x-admin.icon name="book"/>{{ $resource->course?->title ?? 'بدون دوره' }}</span>
            <span><x-admin.icon name="users"/>{{ $resource->classroom?->name ?? 'در سطح دوره' }}</span>
          </div>
          <div class="adm-card-foot">
            <strong class="adm-price {{ $resource->is_free ? 'is-free' : '' }}">{{ $resource->is_free ? 'رایگان' : number_format((int) $resource->price).' تومان' }}</strong>
            <span class="adm-muted">{{ $visibilityOptions[$resource->visibility] ?? $resource->visibility }}</span>
          </div>
          <div class="adm-kv">
            <span>دریافت فایل: <b>{{ $resource->downloadable ? 'مجاز' : 'غیرفعال' }}</b></span>
            <span>رویداد دسترسی: <b class="adm-num">{{ number_format($resource->access_logs_count) }}</b></span>
            <span>فایل: <b>{{ $resource->file_path ? basename($resource->file_path) : 'ثبت نشده' }}</b></span>
          </div>
          <div class="adm-actions">
            @if($resource->file_path)<x-admin.btn size="sm" variant="line" :href="route('admin.resources.download', $resource)">دریافت فایل</x-admin.btn>@endif
            <x-admin.btn size="sm" variant="soft" icon="edit" data-modal-open="resource-modal" data-title="ویرایش منبع" :data-fill="json_encode($fill, JSON_UNESCAPED_UNICODE)" :data-action="route('admin.resources.update', $resource)" data-method="PATCH">ویرایش</x-admin.btn>
            <x-admin.btn size="sm" variant="bad" icon="trash" data-modal-open="resource-delete" :data-name="$resource->title" :data-action="route('admin.resources.destroy', $resource)"/>
          </div>
        </div>
      </article>
    @empty
      <x-admin.empty text="هنوز منبعی ثبت نشده است."/>
    @endforelse
  </div>
  <x-slot:footer>{{ $resources->links() }}</x-slot:footer>
</x-admin.panel>

@if(!$teacherView)
<x-admin.panel title="درخواست‌های خرید در انتظار بررسی" desc="پس از تطبیق رسید بانکی با واریز واقعی، خرید را تأیید کنید." flush data-tone="shop">
    <div class="adm-grid adm-cols-2">
      @forelse($purchases as $purchase)
        <article class="adm-card">
          <div class="adm-card-body">
            <div class="adm-card-title">
              <div><h3>{{ $purchase->resource?->title ?? 'محصول حذف‌شده' }}</h3><p>{{ $purchase->student?->name ?? 'دانش‌آموز نامشخص' }}</p></div>
              <x-admin.badge tone="warn">در انتظار بررسی</x-admin.badge>
            </div>
            <div class="adm-meta">
              <span>مبلغ: <b class="adm-num">{{ number_format($purchase->amount) }} تومان</b></span>
              <span>زمان درخواست: {{ \App\Support\PersianDate::format($purchase->created_at, 'Y/m/d H:i') }}</span>
            </div>
            <div class="adm-kv"><span>شناسه رسید: <b class="adm-ltr">{{ $purchase->provider_reference }}</b></span></div>
            @if(auth()->user()->hasRole('admin'))
              <form method="POST" action="{{ route('admin.resources.purchases.verify', $purchase) }}" onsubmit="return confirm('فقط پس از بررسی واریز واقعی، خرید را تأیید کنید.')">
                @csrf @method('PATCH')
                <button class="adm-btn adm-btn-primary adm-btn-sm" type="submit">تأیید خرید پس از تطبیق رسید</button>
              </form>
            @else
              <span class="adm-muted">در انتظار تأیید مدیر سامانه</span>
            @endif
          </div>
        </article>
      @empty
        <div class="adm-queue-empty"><x-admin.icon name="check"/><b>درخواست خرید معوقی وجود ندارد</b><span>در حال حاضر همه درخواست‌ها بررسی شده‌اند.</span></div>
      @endforelse
    </div>
    <x-slot:footer>{{ $purchases->links() }}</x-slot:footer>
</x-admin.panel>
@endif

@endsection

@push('modals')
<x-admin.modal :demo="false" id="resource-modal" title="منبع آموزشی جدید" size="lg" :action="route('admin.resources.store')">
  <x-admin.legend title="اطلاعات و فایل"/>
  <x-admin.input label="عنوان منبع" name="title" wide required/>
  <x-admin.textarea label="توضیحات" name="description" wide/>
  <x-admin.select label="نوع منبع" name="kind" :options="$kindOptions" required/>
  <x-admin.input label="فایل (حداکثر ۳۰ مگابایت)" name="file" type="file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.zip"/>
  <x-admin.select label="دوره" name="course_id" :options="$courseOptions" placeholder="بدون دوره"/>
  <x-admin.select label="کلاس" name="classroom_id" :options="$classroomOptions" placeholder="سطح کل دوره"/>
  <x-admin.legend title="قیمت و دسترسی"/>
  <x-admin.switch label="منبع رایگان است" name="is_free" :checked="true"/>
  <x-admin.input label="قیمت (تومان)" name="price" type="number" min="0"/>
  <x-admin.select label="مخاطبان مجاز" name="visibility" :options="$visibilityOptions" required/>
  <x-admin.input label="زمان انتشار (اختیاری)" name="release_at" type="datetime-local"/>
  <x-admin.switch label="امکان دریافت فایل" name="downloadable" :checked="true"/>
  <x-admin.select label="وضعیت" name="status" :options="$statusOptions" required/>
</x-admin.modal>
<x-admin.confirm :demo="false" id="resource-delete" title="حذف منبع" text="منبع و فایل خصوصی آن حذف می‌شود؛ رویدادهای دسترسی برای گزارش امنیتی حفظ می‌شوند."/>
@endpush
