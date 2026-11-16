@extends('admin.layouts.app')
@section('title', 'محتوای دوره')

@section('content')
@php
  $course = $course ?? ['id' => 1, 'title' => 'ریاضی تکمیلی دهم'];
  $includes = ['video' => 'ویدیوهای ضبط‌شده', 'live' => 'کلاس زنده', 'resources' => 'جزوه و منابع', 'exams' => 'آزمون و تکلیف', 'qa' => 'پرسش و پاسخ'];
  $validity = ['365' => '۱ سال', '180' => '۶ ماه', '90' => '۳ ماه', '30' => '۱ ماه', '0' => 'نامحدود'];
  $plans = $plans ?? [
    ['id' => 1, 'name' => 'پایه', 'includes' => ['video', 'resources'], 'price' => 900000, 'sale_price' => '', 'validity' => '365', 'is_active' => 1, 'is_featured' => 0, 'buyers' => 61],
    ['id' => 2, 'name' => 'پیشرفته', 'includes' => ['video', 'live', 'resources', 'exams'], 'price' => 1800000, 'sale_price' => 1590000, 'validity' => '365', 'is_active' => 1, 'is_featured' => 1, 'buyers' => 48],
    ['id' => 3, 'name' => 'ویژه', 'includes' => ['video', 'live', 'resources', 'exams', 'qa'], 'price' => 2600000, 'sale_price' => '', 'validity' => '365', 'is_active' => 1, 'is_featured' => 0, 'buyers' => 11],
    ['id' => 4, 'name' => 'فقط کلاس زنده', 'includes' => ['live'], 'price' => 1200000, 'sale_price' => '', 'validity' => '90', 'is_active' => 0, 'is_featured' => 0, 'buyers' => 0],
  ];
  $cohorts = $cohorts ?? [['id' => 1, 'title' => 'گروه شنبه‌ها — استاد احمدی', 'when' => 'شنبه · ۱۷:۰۰', 'members' => 22, 'cap' => 25], ['id' => 2, 'title' => 'گروه دوشنبه‌ها — استاد رضایی', 'when' => 'دوشنبه · ۱۸:۳۰', 'members' => 18, 'cap' => 25]];
  $chapters = $chapters ?? [
    ['id' => 1, 'title' => 'مبانی ریاضی', 'lessons' => [
      ['id' => 11, 'title' => 'مقدمه و معرفی', 'duration' => 12, 'access' => 'free'],
      ['id' => 12, 'title' => 'معادلات و نامعادلات', 'duration' => 34, 'access' => 'paid'],
      ['id' => 13, 'title' => 'تمرین فصل', 'duration' => 18, 'access' => 'paid'],
    ]],
    ['id' => 2, 'title' => 'هندسه', 'lessons' => [['id' => 21, 'title' => 'مثلث‌ها', 'duration' => 41, 'access' => 'paid']]],
  ];
  $lessonCount = collect($chapters)->sum(fn ($c) => count($c['lessons']));
@endphp

<x-admin.page-head tone="edu" icon="book" :title="'محتوای دوره: '.$course['title']" desc="هسته دوره: فصل‌ها و درس‌ها، پلن‌های فروش و گروه‌های آموزشی (کلاس‌ها) همین‌جا به هم وصل می‌شوند"
  :back-url="url('admin/courses')" back-label="دوره‌ها">
  <x-admin.btn icon="plus" data-modal-open="chapter-modal">فصل جدید</x-admin.btn>
</x-admin.page-head>

<div class="adm-stats">
  <x-admin.stat tone="edu" icon="cls" label="فصل‌ها" :value="count($chapters)" :hint="$lessonCount.' درس'"/>
  <x-admin.stat tone="shop" icon="tag" label="پلن‌های فروش" :value="count($plans)" hint="۳ فعال"/>
  <x-admin.stat tone="edu" icon="users" label="کلاس‌ها (گروه‌ها)" :value="count($cohorts)"/>
  <x-admin.stat tone="shop" icon="lock" label="ثبت‌نام فعال" value="120"/>
</div>

<x-admin.panel title="پلن‌های فروش" desc="هر پلن مشخص می‌کند خریدار به کدام امکانات دوره دسترسی دارد؛ قیمت فقط روی پلن است" flush data-tone="shop">
  <x-slot:actions><x-admin.btn size="sm" icon="plus" data-modal-open="plan-modal">پلن جدید</x-admin.btn></x-slot:actions>
  <div class="adm-table-wrap"><table class="adm-table">
    <thead><tr><th>پلن</th><th>امکانات</th><th>قیمت (تومان)</th><th>اعتبار</th><th>خریداران</th><th>وضعیت</th><th></th></tr></thead>
    <tbody>
      @foreach ($plans as $pl)
        <tr>
          <td><b>{{ $pl['name'] }}</b>@if ($pl['is_featured'])<small><x-admin.badge tone="info" class="is-plain">پیشنهادی</x-admin.badge></small>@endif</td>
          <td><x-admin.access-flags :flags="$pl['includes']"/></td>
          <td class="adm-num is-nowrap"><b>{{ number_format($pl['sale_price'] ?: $pl['price']) }}</b>@if ($pl['sale_price'])<small><s>{{ number_format($pl['price']) }}</s></small>@endif</td>
          <td>{{ $validity[$pl['validity']] }}</td>
          <td class="adm-num">{{ $pl['buyers'] }}</td>
          <td>@if ($pl['is_active'])<x-admin.badge tone="ok">فعال</x-admin.badge>@else<x-admin.badge tone="mute">غیرفعال</x-admin.badge>@endif</td>
          <td><x-admin.row-actions edit="plan-modal" del="plan-del" :row="$pl" :name="$pl['name']" :url="url('admin/plans/'.$pl['id'])" edit-title="ویرایش پلن"/></td>
        </tr>
      @endforeach
    </tbody>
  </table></div>
</x-admin.panel>

<div class="adm-split">
  <section class="adm-stack" data-tone="edu" aria-label="فصل‌ها">
    @foreach ($chapters as $i => $ch)
      <div class="adm-chapter">
        <div class="adm-chapter-head" data-toggle-chapter>
          <span class="adm-chapter-no adm-num">{{ $i + 1 }}</span>
          <div class="adm-grow"><h3>{{ $ch['title'] }}</h3><small class="adm-muted adm-num">{{ count($ch['lessons']) }} درس</small></div>
          <x-admin.btn variant="line" size="sm" icon="plus" data-modal-open="lesson-modal" :data-action="url('admin/courses/'.$course['id'].'/chapters/'.$ch['id'].'/lessons')">درس</x-admin.btn>
          <x-admin.btn variant="soft" size="sm" icon="edit" data-modal-open="chapter-modal" data-title="ویرایش فصل" :data-fill="json_encode(['title' => $ch['title'], 'position' => $i + 1], JSON_UNESCAPED_UNICODE)" :data-action="url('admin/chapters/'.$ch['id'])" data-method="PUT" aria-label="ویرایش فصل"/>
          <x-admin.btn variant="bad" size="sm" icon="trash" data-modal-open="chapter-del" :data-name="$ch['title']" :data-action="url('admin/chapters/'.$ch['id'])" aria-label="حذف فصل"/>
        </div>
        <div class="adm-chapter-body" data-sortable>
          @foreach ($ch['lessons'] as $l)
            <div class="adm-lesson" data-sort-item data-id="{{ $l['id'] }}">
              <span class="adm-grip" data-grip title="برای جابه‌جایی بکشید"><x-admin.icon name="grip"/></span>
              <span class="adm-lesson-ico {{ $l['access'] === 'paid' ? 'is-paid' : '' }}"><x-admin.icon :name="$l['access'] === 'paid' ? 'lock' : 'video'"/></span>
              <div class="adm-grow"><b>{{ $l['title'] }}</b><small class="adm-num">{{ $l['duration'] }} دقیقه</small></div>
              @if ($l['access'] === 'paid')<x-admin.badge tone="info">با مجوز ویدیو</x-admin.badge>@else<x-admin.badge tone="ok">پیش‌نمایش رایگان</x-admin.badge>@endif
              <x-admin.btn variant="soft" size="sm" icon="edit" data-modal-open="lesson-modal" data-title="ویرایش درس" :data-fill="json_encode($l, JSON_UNESCAPED_UNICODE)" :data-action="url('admin/lessons/'.$l['id'])" data-method="PUT" aria-label="ویرایش درس"/>
              <x-admin.btn variant="bad" size="sm" icon="trash" data-modal-open="lesson-del" :data-name="$l['title']" :data-action="url('admin/lessons/'.$l['id'])" aria-label="حذف درس"/>
            </div>
          @endforeach
        </div>
      </div>
    @endforeach
  </section>

  <aside class="adm-stack">
    <x-admin.panel title="کلاس‌های این دوره" desc="هر کلاس یک گروه آموزشی با جلسات زنده خودش است" flush data-tone="edu">
      <x-slot:actions><x-admin.btn size="sm" variant="line" :href="url('admin/classes')">مدیریت</x-admin.btn></x-slot:actions>
      <div class="adm-side-list">
        @foreach ($cohorts as $c)
          <div><span class="adm-stat-ico" data-tone="edu" style="width:38px;height:38px;border-radius:11px"><x-admin.icon name="cls"/></span><div class="adm-grow"><b>{{ $c['title'] }}</b><div class="adm-muted adm-num">{{ $c['when'] }}</div></div><x-admin.badge tone="mute" class="is-plain"><span class="adm-num">{{ $c['members'] }}/{{ $c['cap'] }}</span></x-admin.badge></div>
        @endforeach
      </div>
    </x-admin.panel>

    <x-admin.panel title="ویدیوی معرفی" desc="برای همه قابل مشاهده است" data-tone="edu">
      <div class="adm-stack" style="gap:12px">
        <div class="adm-video"><x-admin.icon name="play"/></div>
        <form method="POST" action="{{ url('admin/courses/'.$course['id'].'/teaser') }}" enctype="multipart/form-data" data-demo data-demo-message="ویدیوی معرفی ذخیره شد (نمایشی)">
          @csrf
          <x-admin.file label="جایگزینی ویدیو" name="teaser" accept="video/*" :wide="false" icon="video" hint="MP4، حداکثر ۵۰۰ مگابایت"/>
          <x-admin.btn type="submit" variant="line" style="width:100%;margin-top:12px">ذخیره ویدیو</x-admin.btn>
        </form>
      </div>
    </x-admin.panel>
  </aside>
</div>
@endsection

@push('modals')
<x-admin.modal id="plan-modal" title="پلن جدید" size="lg" :action="url('admin/courses/'.$course['id'].'/plans')">
  <x-admin.input label="نام پلن" name="name" placeholder="مثلاً پیشرفته" wide required/>
  <x-admin.chips label="امکانات این پلن" name="includes" :options="$includes" hint="می‌توان فقط ویدیو یا فقط کلاس زنده فروخت"/>
  <x-admin.legend title="قیمت و اعتبار"/>
  <x-admin.input label="قیمت" name="price" type="number" min="0" step="1000" addon="تومان" required/>
  <x-admin.input label="قیمت با تخفیف" name="sale_price" type="number" min="0" step="1000" addon="تومان" hint="خالی = بدون تخفیف"/>
  <x-admin.select label="مدت اعتبار مجوز" name="validity" :options="$validity"/>
  <x-admin.switch label="پلن پیشنهادی" name="is_featured" hint="با نشان ویژه در صفحه دوره"/>
  <x-admin.switch label="پلن فعال باشد" name="is_active" :checked="true" wide/>
</x-admin.modal>

<x-admin.modal id="chapter-modal" title="فصل جدید" size="sm" :action="url('admin/courses/'.$course['id'].'/chapters')">
  <x-admin.input label="عنوان فصل" name="title" wide required/>
  <x-admin.input label="ترتیب" name="position" type="number" min="1" wide/>
</x-admin.modal>

<x-admin.modal id="lesson-modal" title="درس جدید" size="lg" :action="url('admin/lessons')">
  <x-admin.input label="عنوان درس" name="title" wide required/>
  <x-admin.chips label="چه کسی می‌تواند ببیند؟" name="access" :multiple="false" :options="['free' => 'پیش‌نمایش رایگان', 'paid' => 'دارندگان مجوز «ویدیو»']" :checked="['paid']"/>
  <x-admin.input label="مدت (دقیقه)" name="duration" type="number" min="1"/>
  <x-admin.file label="ویدیوی درس" name="video" accept="video/*" icon="video" hint="ویدیوهای محافظت‌شده با اسپات‌پلیر پخش می‌شوند"/>
  <x-admin.file label="جزوه یا فایل پیوست (اختیاری)" name="attachment" accept=".pdf,.zip" icon="file" hint="PDF یا ZIP"/>
</x-admin.modal>

<x-admin.confirm id="plan-del" title="حذف پلن" text="ثبت‌نام‌های قبلی این پلن حفظ می‌شود ولی فروش جدید متوقف می‌شود."/>
<x-admin.confirm id="chapter-del" title="حذف فصل" text="فصل و همهٔ درس‌های داخل آن حذف می‌شود."/>
<x-admin.confirm id="lesson-del" title="حذف درس" text="درس و ویدیوی آن حذف می‌شود."/>
@endpush
