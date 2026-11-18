@extends('admin.layouts.app')
@section('title', 'بنرهای سئو')

@section('content')
@php
  $statusLabels = ['active' => 'فعال', 'draft' => 'پیش‌نویس', 'inactive' => 'غیرفعال'];
  $placementLabels = ['home_hero' => 'بنر اصلی صفحه خانه', 'sitewide' => 'سراسری در بلاگ', 'articles_top' => 'بالای فهرست مقاله‌ها', 'articles_sidebar' => 'ستون کناری مقاله'];
@endphp
@include('admin.seo.partials.workspace-tabs')
<x-admin.page-head tone="shop" icon="image" title="بنرها و جایگاه‌های تبلیغاتی" desc="مدیریت بنرهای سایت با متن جایگزین، مقصد و بازه نمایش">
  <x-admin.btn icon="plus" data-modal-open="banner-modal">ثبت بنر جدید</x-admin.btn>
</x-admin.page-head>

<div class="adm-stats">
  <x-admin.stat tone="shop" icon="image" label="کل بنرها" :value="number_format($banners->total())"/>
  <x-admin.stat tone="shop" icon="check" label="فعال" :value="number_format($activeBannerCount)"/>
  <x-admin.stat tone="shop" icon="edit" label="پیش‌نویس" :value="number_format($draftBannerCount)"/>
  <x-admin.stat tone="shop" icon="pause" label="غیرفعال" :value="number_format($inactiveBannerCount)"/>
  <x-admin.stat tone="shop" icon="image" label="اسلایدهای ثبت‌شده Hero" :value="number_format($homeHeroCount).' / 3'"/>
  <p class="adm-muted wide">اسلایدر Hero صفحه اصلی دقیقاً ۳ جایگاه دارد. برای هر اسلاید یک بنر با جایگاه «بنر اصلی صفحه خانه» ثبت کنید.</p>
</div>

<x-admin.panel flush>
  <div class="adm-table-wrap"><table class="adm-table">
    <thead><tr><th>بنر</th><th>جایگاه</th><th>مقصد</th><th>بازه نمایش</th><th>وضعیت</th><th>اقدام</th></tr></thead>
    <tbody>
      @forelse($banners as $banner)
        @php
          $fill = [
            'title' => $banner->title,
            'alt_text' => $banner->alt_text,
            'image_position' => $banner->image_position ?: '50% 50%',
            'placement' => $banner->placement,
            'target_url' => $banner->target_url,
            'open_in_new_tab' => (bool) $banner->open_in_new_tab,
            'sort_order' => $banner->sort_order,
            'starts_at' => $banner->starts_at?->format('Y-m-d\TH:i'),
            'ends_at' => $banner->ends_at?->format('Y-m-d\TH:i'),
            'status' => $banner->status,
          ];
        @endphp
        <tr>
          <td><div style="display:flex;align-items:center;gap:10px"><img src="{{ Storage::disk('public')->url($banner->image_path) }}" alt="{{ $banner->alt_text }}" loading="lazy" style="width:76px;height:48px;object-fit:cover;border-radius:8px"><div><b>{{ $banner->title }}</b><small>{{ $banner->alt_text }}</small></div></div></td>
          <td>{{ $placementLabels[$banner->placement] ?? $banner->placement }}</td>
          <td>{{ $banner->target_url ?: '—' }}</td>
          <td>{{ $banner->starts_at?->format('Y/m/d') ?? 'بدون شروع' }} تا {{ $banner->ends_at?->format('Y/m/d') ?? 'بدون پایان' }}</td>
          <td><x-admin.badge :tone="$banner->status === 'active' ? 'ok' : 'mute'">{{ $statusLabels[$banner->status] ?? $banner->status }}</x-admin.badge></td>
          <td><div class="adm-actions">
            <x-admin.btn size="sm" variant="soft" icon="edit" data-modal-open="banner-modal" data-title="ویرایش بنر" :data-fill="json_encode($fill, JSON_UNESCAPED_UNICODE)" :data-action="route('admin.seo.banners.update', $banner)" data-method="PATCH">ویرایش</x-admin.btn>
            <x-admin.btn size="sm" variant="bad" icon="trash" data-modal-open="banner-delete" :data-name="$banner->title" :data-action="route('admin.seo.banners.destroy', $banner)"/>
          </div></td>
        </tr>
      @empty
        <tr><td colspan="6">بنری ثبت نشده است.</td></tr>
      @endforelse
    </tbody>
  </table></div>
  <x-slot:footer>{{ $banners->links() }}</x-slot:footer>
</x-admin.panel>
@endsection

@push('modals')
<x-admin.modal :demo="false" id="banner-modal" title="ثبت بنر جدید" size="lg" :action="route('admin.seo.banners.store')">
  <x-admin.legend title="محتوای بنر"/>
  <x-admin.input label="عنوان داخلی" name="title" required/>
  <x-admin.input label="متن جایگزین تصویر" name="alt_text" required/>
  <x-admin.input label="تصویر بنر" name="image" type="file" accept="image/jpeg,image/png,image/webp" data-preview-ratio="16 / 9" data-preview-mobile-ratio="4 / 5" data-preview-fit="cover"/>
  <x-admin.input label="موقعیت تصویر (افقی و عمودی)" name="image_position" placeholder="50% 50%" hint="پیش‌نمایش دسکتاپ ۱۶:۹ و موبایل ۴:۵ است؛ در هر دو حالت برش نهایی با object-fit: cover شبیه‌سازی می‌شود. برای جابه‌جایی نقطه کانونی درصد افقی و عمودی را تنظیم کنید."/>
  <x-admin.select label="جایگاه نمایش" name="placement" :options="$placementLabels" required/>
  <x-admin.input label="نشانی مقصد" name="target_url" placeholder="/courses یا https://..."/>
  <x-admin.switch label="باز شدن در زبانه جدید" name="open_in_new_tab"/>
  <x-admin.input label="اولویت نمایش" name="sort_order" type="number" min="0" :value="0" required/>
  <x-admin.input label="شروع نمایش" name="starts_at" type="datetime-local"/>
  <x-admin.input label="پایان نمایش" name="ends_at" type="datetime-local"/>
  <x-admin.select label="وضعیت" name="status" :options="$statusLabels" required/>
</x-admin.modal>
<x-admin.confirm :demo="false" id="banner-delete" title="حذف بنر" text="تصویر بنر نیز از فضای ذخیره‌سازی حذف می‌شود."/>
@endpush
