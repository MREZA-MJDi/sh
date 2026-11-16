@extends('admin.layouts.app')
@section('title', 'جلسات با اولیا')

@section('content')
@php
  $students = ['1' => 'سارا احمدی', '2' => 'علی رضایی', '3' => 'نگار کریمی', '4' => 'رضا مرادی'];
  $parents = ['2' => 'حسین احمدی', '1' => 'مریم حسینی', '3' => 'زهرا کریمی', '4' => 'اکبر کاظمی'];
  $hosts = ['sheikhan' => 'آقای شیخان', 'teacher' => 'مدرس مربوطه', 'both' => 'آقای شیخان و مدرس'];
  $modes = ['in_person' => 'حضوری', 'online' => 'آنلاین', 'phone' => 'تلفنی'];
  $status = ['planned' => ['info', 'هماهنگ‌شده'], 'done' => ['ok', 'برگزارشده'], 'canceled' => ['mute', 'لغوشده']];
  $rows = $meetings ?? [
    ['id' => 1, 'student_id' => '2', 'parent_id' => '1', 'host' => 'sheikhan', 'mode' => 'in_person', 'date' => '1405-08-27', 'time' => '18:00', 'duration' => 30, 'place' => 'دفتر آکادمی', 'topic' => 'افت نمره فیزیک', 'notify' => 1, 'status' => 'planned'],
    ['id' => 2, 'student_id' => '1', 'parent_id' => '2', 'host' => 'both', 'mode' => 'online', 'date' => '1405-08-28', 'time' => '17:00', 'duration' => 30, 'place' => 'https://www.skyroom.online/ch/sheikhan/parents', 'topic' => 'برنامه مطالعاتی امتحانات', 'notify' => 1, 'status' => 'planned'],
    ['id' => 3, 'student_id' => '3', 'parent_id' => '3', 'host' => 'sheikhan', 'mode' => 'phone', 'date' => '1405-08-20', 'time' => '19:30', 'duration' => 15, 'place' => '', 'topic' => 'انتخاب رشته', 'notify' => 1, 'status' => 'done'],
  ];
@endphp

<x-admin.page-head tone="comm" icon="cal" title="جلسات با اولیا" desc="زمان جلسه آقای شیخان با ولی هر دانش‌آموز را هماهنگ کنید؛ در پنل ولی و دانش‌آموز اطلاع‌رسانی می‌شود">
  <x-admin.btn icon="plus" data-modal-open="meeting-modal">هماهنگی جلسه</x-admin.btn>
</x-admin.page-head>

<div class="adm-stats">
  <x-admin.stat tone="comm" icon="cal" label="جلسات پیش‌رو" value="6"/>
  <x-admin.stat tone="comm" icon="clock" label="این هفته" value="3"/>
  <x-admin.stat tone="comm" icon="check" label="برگزارشده این ماه" value="14"/>
  <x-admin.stat tone="comm" icon="alert" label="لغوشده" value="1"/>
</div>

<x-admin.panel flush data-filter data-tone="comm">
  <x-admin.toolbar placeholder="جستجوی دانش‌آموز، ولی یا موضوع…">
    <x-admin.filter-select key="mode" placeholder="همه روش‌ها" :options="$modes"/>
    <div class="adm-toolbar-end"><x-admin.seg :options="['planned' => 'هماهنگ‌شده', 'done' => 'برگزارشده', 'canceled' => 'لغوشده']"/></div>
  </x-admin.toolbar>

  <div class="adm-table-wrap"><table class="adm-table">
    <thead><tr><th>زمان</th><th>دانش‌آموز / ولی</th><th>با</th><th>روش</th><th>موضوع</th><th>وضعیت</th><th></th></tr></thead>
    <tbody data-filter-list>
      @foreach ($rows as $r)
        <tr data-name="{{ $students[$r['student_id']].' '.$parents[$r['parent_id']].' '.$r['topic'] }}" data-state="{{ $r['status'] }}" data-mode="{{ $r['mode'] }}">
          <td class="adm-num is-nowrap"><b>{{ $r['date'] }}</b><small>{{ $r['time'] }} · {{ $r['duration'] }} دقیقه</small></td>
          <td><x-admin.person :name="$students[$r['student_id']]" :sub="'ولی: '.$parents[$r['parent_id']]"/></td>
          <td>{{ $hosts[$r['host']] }}</td>
          <td>{{ $modes[$r['mode']] }}</td>
          <td>{{ $r['topic'] }}</td>
          <td><x-admin.badge :tone="$status[$r['status']][0]">{{ $status[$r['status']][1] }}</x-admin.badge></td>
          <td><x-admin.row-actions edit="meeting-modal" del="meeting-del" :row="$r" :name="$r['topic']" :url="url('admin/meetings/'.$r['id'])" edit-title="ویرایش جلسه"/></td>
        </tr>
      @endforeach
    </tbody>
  </table></div>
  <x-admin.empty data-empty class="adm-hide" text="جلسه‌ای پیدا نشد."/>
  <x-slot:footer><x-admin.pager :current="1" :total="21" :per="3"/></x-slot:footer>
</x-admin.panel>
@endsection

@push('modals')
<x-admin.modal id="meeting-modal" title="هماهنگی جلسه جدید" size="lg" :action="url('admin/meetings')">
  <x-admin.legend title="شرکت‌کنندگان"/>
  <x-admin.select label="دانش‌آموز" name="student_id" :options="$students" required/>
  <x-admin.select label="ولی" name="parent_id" :options="$parents" required hint="ولی‌های متصل به دانش‌آموز اول فهرست می‌آیند"/>
  <x-admin.select label="جلسه با" name="host" :options="$hosts"/>
  <x-admin.input label="موضوع جلسه" name="topic" required/>

  <x-admin.legend title="زمان و مکان"/>
  <x-admin.input label="تاریخ" name="date" placeholder="۱۴۰۵/۰۸/۲۷" required/>
  <x-admin.input label="ساعت" name="time" type="time" required/>
  <x-admin.chips label="روش برگزاری" name="mode" :multiple="false" :options="$modes" :checked="['in_person']" :wide="false"/>
  <x-admin.input label="مدت" name="duration" type="number" min="10" step="5" addon="دقیقه"/>
  <div data-show-if="mode=in_person|online"><x-admin.input label="مکان یا لینک جلسه" name="place" wide/></div>

  <x-admin.legend title="اطلاع‌رسانی"/>
  <x-admin.switch label="اطلاع در پنل ولی و دانش‌آموز" name="notify" :checked="true" hint="زمان جلسه در هر دو پنل نمایش داده می‌شود" wide/>
  <x-admin.select label="وضعیت" name="status" :options="['planned' => 'هماهنگ‌شده', 'done' => 'برگزارشده', 'canceled' => 'لغوشده']"/>
</x-admin.modal>

<x-admin.confirm id="meeting-del" title="حذف جلسه" text="جلسه از پنل ولی و دانش‌آموز هم حذف می‌شود."/>
@endpush
