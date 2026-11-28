@extends('admin.layouts.app')
@section('title', 'نظرات دانش‌آموزان')

@section('content')
@php $statusLabels = ['draft' => 'پیش‌نویس', 'published' => 'منتشرشده', 'archived' => 'بایگانی']; @endphp
<x-admin.page-head tone="shop" icon="check" title="نظرات و تجربه دانش‌آموزان" desc="مدیریت نظرهای تأییدشده برای نمایش در صفحه اصلی">
  <x-admin.btn icon="plus" data-modal-open="testimonial-modal">ثبت نظر جدید</x-admin.btn>
  <x-admin.btn variant="line" :href="route('home').'#homeStories'" target="_blank" rel="noopener">نمایش نظرات در سایت</x-admin.btn>
</x-admin.page-head>

<x-admin.panel flush>
  <div class="adm-table-wrap"><table class="adm-table">
    <thead><tr><th>نام</th><th>سمت / پایه</th><th>نظر</th><th>امتیاز</th><th>وضعیت</th><th>اقدام</th></tr></thead>
    <tbody>
      @forelse($testimonials as $testimonial)
        @php
          $fill = [
            'name' => $testimonial->name,
            'role' => $testimonial->role,
            'quote' => $testimonial->quote,
            'rating' => $testimonial->rating,
            'status' => $testimonial->status,
            'sort_order' => $testimonial->sort_order,
          ];
        @endphp
        <tr>
          <td><b>{{ $testimonial->name }}</b></td>
          <td>{{ $testimonial->role ?: '—' }}</td>
          <td>{{ \Illuminate\Support\Str::limit($testimonial->quote, 160) }}</td>
          <td class="adm-num">{{ $testimonial->rating }} / 5</td>
          <td><x-admin.badge :tone="$testimonial->status === 'published' ? 'ok' : 'warn'">{{ $statusLabels[$testimonial->status] ?? $testimonial->status }}</x-admin.badge></td>
          <td><div class="adm-actions">
            <x-admin.btn size="sm" variant="soft" icon="edit" data-modal-open="testimonial-modal" data-title="ویرایش نظر" :data-fill="json_encode($fill, JSON_UNESCAPED_UNICODE)" :data-action="route('admin.seo.testimonials.update', $testimonial)" data-method="PATCH">ویرایش</x-admin.btn>
            <x-admin.btn size="sm" variant="bad" icon="trash" data-modal-open="testimonial-delete" :data-name="$testimonial->name" :data-action="route('admin.seo.testimonials.destroy', $testimonial)"/>
          </div></td>
        </tr>
      @empty
        <tr><td colspan="6">نظری برای نمایش ثبت نشده است.</td></tr>
      @endforelse
    </tbody>
  </table></div>
  <x-slot:footer>{{ $testimonials->links() }}</x-slot:footer>
</x-admin.panel>
@endsection

@push('modals')
<x-admin.modal :demo="false" id="testimonial-modal" title="ثبت نظر دانش‌آموز" size="md" :action="route('admin.seo.testimonials.store')">
  <x-admin.input label="نام نمایشی" name="name" required/>
  <x-admin.input label="سمت / پایه" name="role"/>
  <x-admin.textarea label="متن نظر" name="quote" wide required/>
  <x-admin.select label="امتیاز" name="rating" :options="['5' => '۵ از ۵', '4' => '۴ از ۵', '3' => '۳ از ۵', '2' => '۲ از ۵', '1' => '۱ از ۵']" required/>
  <x-admin.select label="وضعیت انتشار" name="status" :options="$statusLabels" required/>
  <x-admin.input label="ترتیب نمایش" name="sort_order" type="number" min="0"/>
</x-admin.modal>
<x-admin.confirm :demo="false" id="testimonial-delete" title="حذف نظر" text="این نظر از صفحه اصلی حذف می‌شود."/>
@endpush
