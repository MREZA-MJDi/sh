@extends('admin.layouts.app')
@section('title', 'منابع و محصولات')

@section('content')
@php
  $courses = ['' => 'عمومی (بدون دوره)', '1' => 'ریاضی نهم', '2' => 'فیزیک دهم', '3' => 'زیست یازدهم'];
  $types = ['handout' => 'جزوه', 'book' => 'کتاب', 'sample' => 'نمونه‌سؤال', 'product' => 'محصول فیزیکی'];
  $icons = ['handout' => 'file', 'book' => 'lib', 'sample' => 'exam', 'product' => 'box'];
  $rows = $items ?? [
    ['id' => 1, 'c' => 1, 'title' => 'جزوه کامل هندسه نهم', 'type' => 'handout', 'course_id' => '1', 'access' => 'included', 'price' => 0, 'stock' => '', 'is_active' => 1, 'sales' => 0],
    ['id' => 2, 'c' => 2, 'title' => 'کتاب تست فیزیک دهم', 'type' => 'book', 'course_id' => '2', 'access' => 'paid', 'price' => 340000, 'sale_price' => 290000, 'stock' => '', 'is_active' => 1, 'sales' => 41],
    ['id' => 3, 'c' => 3, 'title' => 'نمونه‌سؤال میان‌ترم زیست', 'type' => 'sample', 'course_id' => '3', 'access' => 'free', 'price' => 0, 'stock' => '', 'is_active' => 1, 'sales' => 0],
    ['id' => 4, 'c' => 4, 'title' => 'بسته لوازم‌التحریر آکادمی', 'type' => 'product', 'course_id' => '', 'access' => 'paid', 'price' => 450000, 'stock' => 35, 'is_active' => 0, 'sales' => 12],
  ];
@endphp

<x-admin.page-head tone="shop" icon="lib" title="منابع و محصولات" desc="جزوه، کتاب و محصولاتی که روی سایت عرضه می‌شوند">
  <x-admin.btn icon="plus" data-modal-open="res-modal">منبع جدید</x-admin.btn>
</x-admin.page-head>

<div class="adm-stats">
  <x-admin.stat tone="shop" icon="lib" label="کل منابع" value="42"/>
  <x-admin.stat tone="shop" icon="down" label="رایگان" value="17" hint="قابل دانلود برای دانش‌آموزان"/>
  <x-admin.stat tone="shop" icon="tag" label="فروش این ماه" value="139"/>
  <x-admin.stat tone="shop" icon="alert" label="موجودی کم" value="2" hint="محصولات فیزیکی"/>
</div>

<div data-filter class="adm-stack">
  <x-admin.panel flush><x-admin.toolbar placeholder="جستجوی منبع یا محصول…">
    <x-admin.filter-select key="type" placeholder="همه نوع‌ها" :options="$types"/>
    <div class="adm-toolbar-end"><x-admin.seg :options="['on' => 'فعال', 'off' => 'غیرفعال']"/></div>
  </x-admin.toolbar></x-admin.panel>

  <div class="adm-grid adm-cols-3" data-filter-list>
    @foreach ($rows as $r)
      <article class="adm-card" data-c="{{ $r['c'] }}" data-state="{{ $r['is_active'] ? 'on' : 'off' }}" data-type="{{ $r['type'] }}" data-name="{{ $r['title'] }}">
        <div class="adm-card-cover">
          <x-admin.badge tone="mute" class="is-plain">{{ $types[$r['type']] }}</x-admin.badge>
          <x-admin.icon :name="$icons[$r['type']]"/>
        </div>
        <div class="adm-card-body">
          <h3>{{ $r['title'] }}</h3>
          <div class="adm-meta">
            <span><x-admin.icon name="book"/>{{ $courses[$r['course_id']] }}</span>
            <span class="adm-num"><x-admin.icon name="tag"/>{{ $r['sales'] }} فروش</span>
            @if ($r['stock'] !== '')<span class="adm-num"><x-admin.icon name="box"/>موجودی {{ $r['stock'] }}</span>@endif
          </div>
          <div class="adm-card-foot">
            @if ($r['access'] === 'included')<span class="adm-price" style="color:var(--info)">جزو پلن دوره</span>
            @elseif ($r['access'] === 'free')<span class="adm-price is-free">رایگان</span>
            @else<span class="adm-price adm-num">{{ number_format($r['sale_price'] ?? $r['price']) }} تومان @isset($r['sale_price'])<s>{{ number_format($r['price']) }}</s>@endisset</span>@endif
            <label class="adm-switch" title="نمایش در سایت"><input type="checkbox" name="is_active" @checked($r['is_active']) aria-label="نمایش در سایت"><i></i></label>
          </div>
          <div class="adm-actions">
            <x-admin.btn variant="soft" size="sm" icon="edit" data-modal-open="res-modal" data-title="ویرایش منبع" :data-fill="json_encode($r, JSON_UNESCAPED_UNICODE)" :data-action="url('admin/resources/'.$r['id'])" data-method="PUT">ویرایش</x-admin.btn>
            <x-admin.btn variant="bad" size="sm" icon="trash" data-modal-open="res-del" :data-name="$r['title']" :data-action="url('admin/resources/'.$r['id'])" aria-label="حذف"/>
          </div>
        </div>
      </article>
    @endforeach
  </div>
  <x-admin.empty data-empty class="adm-hide" text="منبعی پیدا نشد."/>
  <x-admin.pager :current="1" :total="42" :per="4"/>
</div>
@endsection

@push('modals')
<x-admin.modal id="res-modal" title="منبع جدید" size="lg" :action="url('admin/resources')">
  <x-admin.legend title="اطلاعات منبع"/>
  <x-admin.input label="عنوان" name="title" wide required/>
  <x-admin.select label="نوع" name="type" :options="$types"/>
  <x-admin.select label="مربوط به دوره" name="course_id" :options="$courses"/>
  <div data-show-if="type=handout|book|sample"><x-admin.file label="فایل (PDF)" name="file" accept=".pdf,.zip" icon="file" hint="PDF یا ZIP، حداکثر ۵۰ مگابایت"/></div>
  <div data-show-if="type=product"><x-admin.input label="موجودی انبار" name="stock" type="number" min="0" addon="عدد"/></div>
  <x-admin.file label="تصویر" name="cover" accept="image/*" icon="up" :wide="false" hint="تصویر کاور"/>

  <x-admin.legend title="قیمت و نمایش"/>
  <x-admin.chips label="نحوه ارائه" name="access" :multiple="false" :options="['included' => 'جزو امکانات پلن‌های دوره', 'free' => 'رایگان برای همه', 'paid' => 'فروش مستقل']" :checked="['included']"/>
  <div data-show-if="access=paid"><x-admin.input label="قیمت" name="price" type="number" min="0" step="1000" addon="تومان"/><x-admin.input label="قیمت با تخفیف" name="sale_price" type="number" min="0" step="1000" addon="تومان"/></div>
  <x-admin.switch label="در سایت نمایش داده شود" name="is_active" :checked="true" wide/>
</x-admin.modal>

<x-admin.confirm id="res-del" title="حذف منبع" text="منبع از سایت و پنل دانش‌آموزان حذف می‌شود."/>
@endpush
