@extends('admin.layouts.app')
@section('title', 'کلاس‌ها')

@section('content')
@php
  $courses = ['1' => 'ریاضی نهم', '2' => 'فیزیک دهم', '3' => 'زیست یازدهم'];
  $teachers = ['1' => 'دکتر محمدی', '2' => 'خانم صادقی', '3' => 'آقای نوری'];
  $students = ['1' => 'سارا احمدی', '2' => 'علی رضایی', '3' => 'نگار کریمی', '4' => 'رضا مرادی', '5' => 'فاطمه حسینی', '6' => 'کیان صالحی'];
  $days = ['sat' => 'شنبه', 'sun' => 'یکشنبه', 'mon' => 'دوشنبه', 'tue' => 'سه‌شنبه', 'wed' => 'چهارشنبه', 'thu' => 'پنجشنبه'];
  $rows = $classes ?? [
    ['id' => 1, 'c' => 1, 'title' => 'ریاضی نهم — گروه الف', 'course_id' => '1', 'teacher_id' => '1', 'days' => ['sat', 'mon'], 'time' => '16:00', 'capacity' => 25, 'filled' => 22, 'access' => 'free', 'is_active' => 1, 'students' => ['1', '2', '3', '4']],
    ['id' => 2, 'c' => 2, 'title' => 'فیزیک دهم — گروه ب', 'course_id' => '2', 'teacher_id' => '2', 'days' => ['sun', 'tue'], 'time' => '17:30', 'capacity' => 20, 'filled' => 18, 'access' => 'paid', 'price' => 900000, 'is_active' => 1, 'students' => ['2', '5', '6']],
    ['id' => 3, 'c' => 3, 'title' => 'زیست یازدهم — گروه ج', 'course_id' => '3', 'teacher_id' => '3', 'days' => ['wed'], 'time' => '15:00', 'capacity' => 20, 'filled' => 9, 'access' => 'free', 'is_active' => 0, 'students' => ['3', '6']],
  ];
@endphp

<x-admin.page-head tone="edu" icon="cls" title="کلاس‌ها" desc="هر کلاس یک گروه آموزشی از یک دوره است؛ مدرس، برنامه هفتگی و اعضای خودش را دارد">
  <x-admin.btn icon="plus" data-modal-open="class-modal">کلاس جدید</x-admin.btn>
</x-admin.page-head>

<div class="adm-stats">
  <x-admin.stat tone="edu" icon="cls" label="کلاس‌ها" value="3" hint="۲ فعال"/>
  <x-admin.stat tone="edu" icon="users" label="دانش‌آموزان" value="49" hint="عضو کلاس‌ها"/>
  <x-admin.stat tone="edu" icon="check" label="ظرفیت خالی" value="16" hint="از ۶۵ جا"/>
  <x-admin.stat tone="edu" icon="teacher" label="بدون مدرس" value="0" hint="همه تخصیص دارند"/>
</div>

<div data-filter class="adm-stack">
  <x-admin.panel flush><x-admin.toolbar placeholder="جستجوی کلاس، دوره یا مدرس…">
    <x-admin.filter-select key="teacher" placeholder="همه مدرسان" :options="$teachers"/>
    <div class="adm-toolbar-end"><x-admin.seg :options="['on' => 'فعال', 'off' => 'غیرفعال']"/></div>
  </x-admin.toolbar></x-admin.panel>

  <div class="adm-grid adm-cols-3" data-filter-list>
    @foreach ($rows as $r)
      @php $pct = round($r['filled'] / $r['capacity'] * 100); $names = collect($r['students']); @endphp
      <article class="adm-card is-class" data-c="{{ $r['c'] }}" data-state="{{ $r['is_active'] ? 'on' : 'off' }}" data-teacher="{{ $r['teacher_id'] }}" data-name="{{ $r['title'].' '.$courses[$r['course_id']].' '.$teachers[$r['teacher_id']] }}">
        <div class="adm-card-body">
          <div class="adm-card-title">
            <div><h3>{{ $r['title'] }}</h3><p>{{ $courses[$r['course_id']] }}</p></div>
            @if ($r['is_active'])<x-admin.badge tone="ok">فعال</x-admin.badge>@else<x-admin.badge tone="warn">غیرفعال</x-admin.badge>@endif
          </div>
          <div class="adm-kv"><span>مدرس: <b>{{ $teachers[$r['teacher_id']] }}</b></span><span>{{ collect($r['days'])->map(fn ($d) => $days[$d])->join(' و ') }} · <span class="adm-num">{{ $r['time'] }}</span></span></div>
          <div>
            <div class="adm-kv" style="justify-content:space-between"><span>ظرفیت</span><b class="adm-num">{{ $r['filled'] }} از {{ $r['capacity'] }}</b></div>
            <div class="adm-progress" data-tone="edu"><i style="width:{{ $pct }}%"></i></div>
          </div>
          <div class="adm-card-foot">
            <div class="adm-faces">@foreach ($names->take(4) as $s)<x-admin.avatar :name="$students[$s]"/>@endforeach</div>
            <x-admin.badge tone="tone" data-tone="edu" class="is-plain"><span class="adm-num">{{ $r['filled'] }}</span> عضو</x-admin.badge>
          </div>
          <div class="adm-actions">
            <x-admin.btn size="sm" icon="users" data-modal-open="class-students" :data-fill="json_encode(['students' => $r['students']], JSON_UNESCAPED_UNICODE)" :data-action="url('admin/classes/'.$r['id'].'/students')" data-method="PUT">دانش‌آموزان</x-admin.btn>
            <x-admin.btn variant="soft" size="sm" icon="edit" data-modal-open="class-modal" data-title="ویرایش کلاس" :data-fill="json_encode($r, JSON_UNESCAPED_UNICODE)" :data-action="url('admin/classes/'.$r['id'])" data-method="PUT">ویرایش</x-admin.btn>
            <x-admin.btn variant="bad" size="sm" icon="trash" data-modal-open="class-del" :data-name="$r['title']" :data-action="url('admin/classes/'.$r['id'])" aria-label="حذف"/>
          </div>
        </div>
      </article>
    @endforeach
  </div>
  <x-admin.empty data-empty class="adm-hide" text="کلاسی پیدا نشد."/>
  <x-admin.pager :current="1" :total="24" :per="3"/>
</div>
@endsection

@push('modals')
<x-admin.modal id="class-modal" title="کلاس جدید" size="lg" :action="url('admin/classes')">
  <x-admin.legend title="اطلاعات کلاس"/>
  <x-admin.input label="عنوان کلاس" name="title" wide required/>
  <x-admin.select label="دوره" name="course_id" :options="$courses" required/>
  <x-admin.select label="مدرس" name="teacher_id" :options="$teachers" required/>

  <x-admin.legend title="برنامه هفتگی"/>
  <x-admin.chips label="روزهای برگزاری" name="days" :options="$days"/>
  <x-admin.input label="ساعت شروع" name="time" type="time"/>
  <x-admin.input label="ظرفیت" name="capacity" type="number" min="1" addon="نفر"/>

  <x-admin.legend title="دانش‌آموزان"/>
  <x-admin.chips label="اعضای کلاس" name="students" :options="$students" scroll/>

  <x-admin.legend title="وضعیت" desc="دسترسی به کلاس با مجوز «کلاس زنده» در ثبت‌نام دانش‌آموز تعیین می‌شود، نه قیمت کلاس"/>
  <x-admin.switch label="کلاس فعال باشد" name="is_active" :checked="true" wide/>
</x-admin.modal>

<x-admin.modal id="class-students" title="دانش‌آموزان کلاس" method="PUT" :action="url('admin/classes')">
  <x-admin.chips label="افزودن / حذف دانش‌آموز" name="students" :options="$students" scroll/>
</x-admin.modal>

<x-admin.confirm id="class-del" title="حذف کلاس" text="کلاس حذف می‌شود؛ دوره و حساب دانش‌آموزان دست‌نخورده می‌ماند."/>
@endpush
