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

<x-admin.panel flush>
  <div class="adm-table-wrap"><table class="adm-table">
    <thead><tr><th>منبع</th><th>دوره / کلاس</th><th>نوع و دسترسی</th><th>قیمت</th><th>رویدادهای دسترسی</th><th>وضعیت</th><th></th></tr></thead>
    <tbody>
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
            'release_at' => $resource->release_at?->format('Y-m-d\TH:i'),
            'downloadable' => (bool) $resource->downloadable,
            'status' => $resource->status,
          ];
        @endphp
        <tr>
          <td><b>{{ $resource->title }}</b><small>{{ $resource->uploader?->name ?? '—' }} · {{ $resource->file_path ? basename($resource->file_path) : 'بدون فایل' }}</small></td>
          <td>{{ $resource->course?->title ?? '—' }}<small>{{ $resource->classroom?->name ?? 'سطح دوره' }}</small></td>
          <td>{{ $kindOptions[$resource->kind] ?? $resource->kind }}<small>{{ $visibilityOptions[$resource->visibility] ?? $resource->visibility }}</small></td>
          <td>{{ $resource->is_free ? 'رایگان' : number_format((int) $resource->price).' تومان' }}</td>
          <td class="adm-num">{{ number_format($resource->access_logs_count) }}</td>
          <td><x-admin.badge :tone="$resource->status === 'published' ? 'ok' : 'warn'">{{ $statusOptions[$resource->status] ?? $resource->status }}</x-admin.badge></td>
          <td><div class="adm-actions">
            @if($resource->file_path)<x-admin.btn size="sm" variant="line" :href="route('admin.resources.download', $resource)">دریافت</x-admin.btn>@endif
            <x-admin.btn size="sm" variant="soft" icon="edit" data-modal-open="resource-modal" data-title="ویرایش منبع" :data-fill="json_encode($fill, JSON_UNESCAPED_UNICODE)" :data-action="route('admin.resources.update', $resource)" data-method="PATCH">ویرایش</x-admin.btn>
            <x-admin.btn size="sm" variant="bad" icon="trash" data-modal-open="resource-delete" :data-name="$resource->title" :data-action="route('admin.resources.destroy', $resource)"/>
          </div></td>
        </tr>
      @empty
        <tr><td colspan="7">هنوز منبعی ثبت نشده است.</td></tr>
      @endforelse
    </tbody>
  </table></div>
  <x-slot:footer>{{ $resources->links() }}</x-slot:footer>
</x-admin.panel>
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
