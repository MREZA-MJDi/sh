@extends('admin.layouts.app')
@section('title', 'بنرها و اسلایدر صفحه اصلی')

@section('content')
@php
  $statusLabels = ['active' => 'فعال', 'draft' => 'پیش‌نویس', 'inactive' => 'غیرفعال'];
  $placementLabels = [
    'home_hero' => 'اسلایدر هیرو صفحه اصلی (Hero Slider)',
    'sitewide' => 'سراسری در بلاگ',
    'articles_top' => 'بالای فهرست مقاله‌ها',
    'articles_sidebar' => 'ستون کناری مقاله'
  ];
  $layoutLabels = [
    'side_image' => 'تصویر در کنار متن (طرح مدرن کارت / مشابه نمونه)',
    'full_background' => 'تصویر تمام‌عرض در پس‌زمینه'
  ];
@endphp
@include('admin.seo.partials.workspace-tabs')
<x-admin.page-head tone="shop" icon="file" title="{{ $selectedPlacement === 'home_hero' ? 'مدیریت اسلایدر Hero صفحه اصلی' : 'بنرها و جایگاه‌های تبلیغاتی' }}" :desc="$selectedPlacement === 'home_hero' ? 'کنترل کامل اسلایدر بالای لندینگ؛ متن‌ها، عنوان، توضیحات، نشان (Badge)، دکمه‌های اقدام (CTA)، تصویر و رنگ‌بندی هر اسلاید را تنظیم کنید.' : 'مدیریت بنرهای سایت با متن جایگزین، مقصد و بازه نمایش'">
  <x-admin.btn icon="plus" data-modal-open="banner-modal">ثبت بنر جدید</x-admin.btn>
</x-admin.page-head>

<div class="adm-stats">
  <x-admin.stat tone="shop" icon="file" label="کل بنرها" :value="number_format($banners->total())"/>
  <x-admin.stat tone="shop" icon="check" label="فعال" :value="number_format($activeBannerCount)"/>
  <x-admin.stat tone="shop" icon="edit" label="پیش‌نویس" :value="number_format($draftBannerCount)"/>
  <x-admin.stat tone="shop" icon="clock" label="غیرفعال" :value="number_format($inactiveBannerCount)"/>
  <x-admin.stat tone="shop" icon="file" label="اسلایدهای ثبت‌شده Hero" :value="number_format($homeHeroCount).' / 3'"/>
  <p class="adm-muted wide">اسلایدهای فعال در جایگاه «اسلایدر هیرو صفحه اصلی» بر اساس اولویت نمایش مرتب می‌شوند و همراه با امکان تغییر خودکار، کشیدن انگشت (Swipe) و کلیدهای ناوبری در بالای صفحه خانه نمایش می‌یابند.</p>
</div>

<x-admin.panel flush>
  <div class="adm-table-wrap"><table class="adm-table">
    <thead>
      <tr>
        <th>تصویر و عنوان</th>
        <th>نشان و توضیحات</th>
        <th>جایگاه</th>
        <th>دکمه‌های اقدام (CTA)</th>
        <th>اولویت</th>
        <th>وضعیت</th>
        <th>اقدام</th>
      </tr>
    </thead>
    <tbody>
      @forelse($banners as $banner)
        @php
          $fill = [
            'title' => $banner->title,
            'alt_text' => $banner->alt_text,
            'subtitle' => $banner->subtitle ?? '',
            'badge' => $banner->badge ?? '',
            'layout_type' => $banner->layout_type ?: 'side_image',
            'bg_color' => $banner->bg_color ?? '',
            'text_color' => $banner->text_color ?? '',
            'image_position' => $banner->image_position ?: '50% 50%',
            'placement' => $banner->placement,
            'button_text' => $banner->button_text ?? '',
            'target_url' => $banner->target_url ?? '',
            'secondary_button_text' => $banner->secondary_button_text ?? '',
            'secondary_button_url' => $banner->secondary_button_url ?? '',
            'open_in_new_tab' => (bool) $banner->open_in_new_tab,
            'sort_order' => $banner->sort_order,
            'starts_at' => $banner->starts_at?->format('Y-m-d\TH:i'),
            'ends_at' => $banner->ends_at?->format('Y-m-d\TH:i'),
            'status' => $banner->status,
          ];
        @endphp
        <tr>
          <td>
            <div style="display:flex;align-items:center;gap:12px">
              <img src="{{ Storage::disk('public')->url($banner->image_path) }}" alt="{{ $banner->alt_text }}" loading="lazy" style="width:84px;height:52px;object-fit:cover;border-radius:8px;border:1px solid rgba(0,0,0,0.08)">
              <div>
                <b style="display:block;font-size:14px">{{ $banner->title }}</b>
                <small style="color:#64748b">{{ $banner->alt_text }}</small>
              </div>
            </div>
          </td>
          <td>
            <div style="max-width:280px">
              @if($banner->badge)
                <span class="adm-chip" style="font-size:11px;margin-bottom:4px;display:inline-block">{{ $banner->badge }}</span>
              @endif
              <p style="margin:2px 0 0;font-size:12px;color:#475569;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $banner->subtitle ?: '—' }}</p>
            </div>
          </td>
          <td><small>{{ $placementLabels[$banner->placement] ?? $banner->placement }}</small></td>
          <td>
            <div style="font-size:12px">
              @if($banner->button_text || $banner->target_url)
                <div><b>دکمه اصلی:</b> {{ $banner->button_text ?: 'پیش‌فرض' }} <small style="color:#64748b">({{ $banner->target_url ?: '—' }})</small></div>
              @endif
              @if($banner->secondary_button_text)
                <div style="color:#64748b"><b>دکمه ۲:</b> {{ $banner->secondary_button_text }}</div>
              @endif
              @if(!$banner->button_text && !$banner->target_url && !$banner->secondary_button_text)
                <span style="color:#94a3b8">—</span>
              @endif
            </div>
          </td>
          <td><b>{{ $banner->sort_order }}</b></td>
          <td><x-admin.badge :tone="$banner->status === 'active' ? 'ok' : ($banner->status === 'draft' ? 'info' : 'mute')">{{ $statusLabels[$banner->status] ?? $banner->status }}</x-admin.badge></td>
          <td><div class="adm-actions">
            <x-admin.btn size="sm" variant="soft" icon="edit" data-modal-open="banner-modal" data-title="ویرایش اسلاید / بنر" :data-fill="json_encode($fill, JSON_UNESCAPED_UNICODE)" :data-action="route('admin.seo.banners.update', $banner)" data-method="PATCH">ویرایش</x-admin.btn>
            <x-admin.btn size="sm" variant="bad" icon="trash" data-modal-open="banner-delete" :data-name="$banner->title" :data-action="route('admin.seo.banners.destroy', $banner)"/>
          </div></td>
        </tr>
      @empty
        <tr><td colspan="7">هیچ اسلاید یا بنری ثبت نشده است. با زدن دکمه «ثبت اسلاید / بنر جدید» اولین اسلاید را اضافه کنید.</td></tr>
      @endforelse
    </tbody>
  </table></div>
  <x-slot:footer>{{ $banners->links() }}</x-slot:footer>
</x-admin.panel>
@endsection

@push('modals')
<x-admin.modal :demo="false" id="banner-modal" title="ثبت بنر جدید" size="lg" :action="route('admin.seo.banners.store')">
  <x-admin.legend title="متن و محتوای اسلاید"/>
  <x-admin.input label="عنوان اصلی اسلاید" name="title" required placeholder="مثال: پایه سوم ابتدایی شیخان یا دوره جامع ریاضی" hint="عنوان برجسته و درشت که در اسلایدر نمایش داده می‌شود."/>
  <x-admin.input label="نشان / برچسب بالای عنوان (Badge)" name="badge" placeholder="مثال: ثبت‌نام سال جدید، ویژه دانش‌آموزان شیخان، تخفیف ویژه" hint="برچسب کوچک و شیک که بالای عنوان اسلاید قرار می‌گیرد."/>
  <x-admin.textarea label="متن توضیحات اسلاید (Subtitle)" name="subtitle" placeholder="توضیح کوتاه و جذاب در مورد این پایه تحصیلی یا دوره آموزشی..." rows="3" hint="متن کامل اسلاید در این قسمت قرار می‌گیرد و جایگزین متن پیش‌فرض خواهد شد."/>

  <x-admin.legend title="تصویر و طراحی ظاهری"/>
  <x-admin.input label="تصویر اسلاید" name="image" type="file" accept="image/jpeg,image/png,image/webp" data-preview-ratio="16 / 9" data-preview-mobile-ratio="4 / 5" data-preview-fit="cover" hint="در حالت ویرایش، در صورت عدم انتخاب فایل جدید، تصویر قبلی حفظ خواهد شد."/>
  <x-admin.input label="متن جایگزین تصویر (Alt Text)" name="alt_text" required placeholder="توضیح تصویر برای سئو و دسترس‌پذیری"/>
  <x-admin.select label="سبک چیدمان اسلاید" name="layout_type" :options="$layoutLabels" selected="side_image" hint="«تصویر در کنار متن» ظاهر مدرن شبیه کارت با گوشه‌های گرد و عکس دانش‌آموز ایجاد می‌کند."/>
  <x-admin.input label="رنگ یا گرادیان پس‌زمینه" name="bg_color" placeholder="linear-gradient(135deg, #4c1d95, #7c3aed) یا #6d28d9" hint="می‌توانید کد هگز یا گرادینت دلخواه بنویسید؛ در صورت خالی ماندن از رنگ بنفش هماهنگ با طرح استفاده می‌شود."/>
  <x-admin.input label="موقعیت تصویر (افقی و عمودی)" name="image_position" placeholder="50% 50%" hint="درصد موقعیت کادر تصویر (پیش‌فرض: 50% 50%)"/>

  <x-admin.legend title="دکمه‌های اقدام (Call to Action)"/>
  <x-admin.input label="متن دکمه اصلی" name="button_text" placeholder="مثال: مشاوره رایگان و ثبت‌نام" hint="متن دکمه اول اسلاید (در صورت خالی بودن، متن پیش‌فرض سایت قرار می‌گیرد)."/>
  <x-admin.input label="نشانی مقصد دکمه اصلی" name="target_url" placeholder="/courses یا https://..."/>
  <x-admin.switch label="باز شدن دکمه اصلی در زبانه جدید" name="open_in_new_tab"/>
  <x-admin.input label="متن دکمه دوم (اختیاری)" name="secondary_button_text" placeholder="مثال: مشاهده دوره‌ها یا اطلاعات بیشتر" hint="اگر خالی باشد دکمه دوم نمایش داده نمی‌شود."/>
  <x-admin.input label="نشانی مقصد دکمه دوم (اختیاری)" name="secondary_button_url" placeholder="/courses یا /contact"/>

  <x-admin.legend title="تنظیمات انتشار و اولویت"/>
  @if($selectedPlacement === 'home_hero')
    <input type="hidden" name="placement" value="home_hero">
  @else
    <x-admin.select label="جایگاه نمایش" name="placement" :options="$placementLabels" :selected="$selectedPlacement ?? 'home_hero'" required/>
  @endif
  <x-admin.input label="اولویت نمایش (ترتیب در اسلایدر)" name="sort_order" type="number" min="0" :value="0" required hint="عدد کمتر = نمایش اول در اسلایدر"/>
  <x-admin.input label="شروع نمایش" name="starts_at" type="datetime-local"/>
  <x-admin.input label="پایان نمایش" name="ends_at" type="datetime-local"/>
  <x-admin.select label="وضعیت انتشار" name="status" :options="$statusLabels" required/>
</x-admin.modal>
<x-admin.confirm :demo="false" id="banner-delete" title="حذف اسلاید / بنر" text="تصویر بنر نیز از فضای ذخیره‌سازی حذف می‌شود."/>
@endpush
