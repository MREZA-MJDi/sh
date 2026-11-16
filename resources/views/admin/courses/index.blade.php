@extends('admin.layouts.app')
@section('title', 'دوره‌ها')

@section('content')
@php
  $teachers = ['1' => 'دکتر محمدی', '2' => 'خانم صادقی', '3' => 'آقای نوری'];
  $grades = ['7' => 'هفتم', '8' => 'هشتم', '9' => 'نهم', '10' => 'دهم', '11' => 'یازدهم', '12' => 'دوازدهم'];
  $rows = $courses ?? [
    ['id' => 1, 'c' => 1, 'icon' => 'cls', 'title' => 'ریاضی نهم', 'teacher_id' => '1', 'grade' => '9', 'is_free' => 0, 'plans' => 3, 'from_price' => 900000, 'cohorts' => 4, 'is_active' => 1, 'students' => 120, 'lessons' => 12, 'description' => 'پوشش کامل کتاب درسی و نمونه‌سؤال‌های امتحانی.'],
    ['id' => 2, 'c' => 2, 'icon' => 'book', 'title' => 'فیزیک دهم', 'teacher_id' => '2', 'grade' => '10', 'is_free' => 0, 'plans' => 2, 'from_price' => 1200000, 'cohorts' => 3, 'is_active' => 1, 'students' => 86, 'lessons' => 9, 'description' => ''],
    ['id' => 3, 'c' => 3, 'icon' => 'users', 'title' => 'زیست یازدهم', 'teacher_id' => '3', 'grade' => '11', 'is_free' => 1, 'plans' => 0, 'from_price' => 0, 'cohorts' => 1, 'is_active' => 0, 'students' => 54, 'lessons' => 7, 'description' => ''],
  ];
@endphp

<x-admin.page-head tone="edu" icon="book" title="دوره‌ها" desc="هر دوره شامل فصل‌ها و درس‌های ویدیویی است">
  <x-admin.btn icon="plus" data-modal-open="course-modal">دوره جدید</x-admin.btn>
</x-admin.page-head>

<div class="adm-stats">
  <x-admin.stat tone="edu" icon="book" label="کل دوره‌ها" value="3" hint="۲ منتشرشده"/>
  <x-admin.stat tone="edu" icon="users" label="دانش‌آموزان" value="260" hint="در همه دوره‌ها"/>
  <x-admin.stat tone="edu" icon="file" label="درس‌ها" value="28" hint="فصل‌بندی‌شده"/>
  <x-admin.stat tone="edu" icon="edit" label="پیش‌نویس" value="1" hint="منتظر انتشار"/>
</div>

<div data-filter class="adm-stack">
  <x-admin.panel flush><x-admin.toolbar placeholder="جستجوی دوره یا مدرس…">
    <x-admin.filter-select key="grade" placeholder="همه پایه‌ها" :options="$grades"/>
    <div class="adm-toolbar-end"><x-admin.seg :options="['on' => 'منتشرشده', 'off' => 'پیش‌نویس']"/></div>
  </x-admin.toolbar></x-admin.panel>

  <div class="adm-grid adm-cols-3" data-filter-list>
    @foreach ($rows as $r)
      <article class="adm-card" data-c="{{ $r['c'] }}" data-state="{{ $r['is_active'] ? 'on' : 'off' }}" data-grade="{{ $r['grade'] }}" data-name="{{ $r['title'].' '.$teachers[$r['teacher_id']] }}">
        <div class="adm-card-cover">
          @if ($r['is_active'])<x-admin.badge tone="ok">منتشرشده</x-admin.badge>@else<x-admin.badge tone="warn">پیش‌نویس</x-admin.badge>@endif
          <x-admin.icon :name="$r['icon']"/>
        </div>
        <div class="adm-card-body">
          <h3>{{ $r['title'] }}</h3>
          <div class="adm-meta">
            <span><x-admin.icon name="user"/>{{ $teachers[$r['teacher_id']] }}</span>
            <span class="adm-num"><x-admin.icon name="users"/>{{ $r['students'] }} دانش‌آموز</span>
            <span class="adm-num"><x-admin.icon name="file"/>{{ $r['lessons'] }} درس</span>
            <span class="adm-num"><x-admin.icon name="cls"/>{{ $r['cohorts'] }} کلاس</span>
            <span class="adm-num"><x-admin.icon name="tag"/>{{ $r['plans'] }} پلن</span>
          </div>
          <div class="adm-card-foot">
            @if ($r['is_free'])<span class="adm-price is-free">رایگان</span>
            @else<span class="adm-price adm-num"><small class="adm-muted" style="font-weight:500">از</small> {{ number_format($r['from_price']) }} تومان</span>@endif
            <label class="adm-switch" title="فعال / غیرفعال"><input type="checkbox" name="is_active" @checked($r['is_active']) aria-label="فعال بودن دوره"><i></i></label>
          </div>
          <div class="adm-actions">
            <x-admin.btn size="sm" icon="book" :href="url('admin/courses/'.$r['id'].'/content')">محتوا</x-admin.btn>
            <x-admin.btn variant="soft" size="sm" icon="edit" data-modal-open="course-modal" data-title="ویرایش دوره" :data-fill="json_encode($r, JSON_UNESCAPED_UNICODE)" :data-action="url('admin/courses/'.$r['id'])" data-method="PUT">ویرایش</x-admin.btn>
            <x-admin.btn variant="bad" size="sm" icon="trash" data-modal-open="course-del" :data-name="$r['title']" :data-action="url('admin/courses/'.$r['id'])" aria-label="حذف"/>
          </div>
        </div>
      </article>
    @endforeach
  </div>
  <x-admin.empty data-empty class="adm-hide" text="دوره‌ای پیدا نشد."/>
  <x-admin.pager :current="1" :total="18" :per="3"/>
</div>
@endsection

@push('modals')
<x-admin.modal id="course-modal" title="دوره جدید" size="lg" :action="url('admin/courses')">
  <x-admin.legend title="اطلاعات دوره"/>
  <x-admin.input label="عنوان دوره" name="title" placeholder="مثلاً ریاضی نهم" wide required/>
  <x-admin.select label="مدرس" name="teacher_id" :options="$teachers" required/>
  <x-admin.select label="پایه" name="grade" :options="$grades" placeholder="همه پایه‌ها"/>
  <x-admin.textarea label="توضیحات" name="description"/>
  <x-admin.file label="تصویر شاخص" name="cover" accept="image/*" hint="نسبت ۱۶ به ۹ پیشنهاد می‌شود" icon="up"/>

  <x-admin.legend title="فروش" desc="قیمت و امکانات هر پلن در صفحه «محتوا» تعریف می‌شود"/>
  <x-admin.switch label="دوره رایگان است" name="is_free" hint="پلن‌ها نادیده گرفته می‌شوند" wide/>

  <x-admin.legend title="انتشار"/>
  <x-admin.switch label="منتشر شود" name="is_active" hint="دوره برای دانش‌آموزان قابل مشاهده است" wide/>
</x-admin.modal>

<x-admin.confirm id="course-del" title="حذف دوره" text="دوره و همهٔ فصل‌ها و درس‌های آن حذف می‌شود."/>
@endpush
