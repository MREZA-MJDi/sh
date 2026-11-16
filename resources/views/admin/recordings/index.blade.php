@extends('admin.layouts.app')
@section('title', 'ویدیوهای ضبط‌شده')

@section('content')
@php
  $courses = ['1' => 'ریاضی نهم', '2' => 'فیزیک دهم', '3' => 'زیست یازدهم'];
  $classes = ['' => 'مشترک بین همه کلاس‌های دوره', '1' => 'ریاضی نهم — الف', '2' => 'فیزیک دهم — ب', '3' => 'زیست یازدهم — ج'];
  $rows = $recordings ?? [
    ['id' => 1, 'title' => 'جلسه ۱۱: نسبت و تناسب', 'course_id' => '1', 'class_id' => '1', 'duration' => 88, 'size' => '1.2 GB', 'access' => 'paid', 'spot' => 1, 'status' => 'ready', 'date' => '1405-08-23'],
    ['id' => 2, 'title' => 'جلسه ۷: حرکت شتاب‌دار', 'course_id' => '2', 'class_id' => '2', 'duration' => 74, 'size' => '980 MB', 'access' => 'paid', 'spot' => 1, 'status' => 'ready', 'date' => '1405-08-21'],
    ['id' => 3, 'title' => 'مرور فصل ۱ زیست', 'course_id' => '3', 'class_id' => '', 'duration' => 52, 'size' => '640 MB', 'access' => 'free', 'spot' => 0, 'status' => 'ready', 'date' => '1405-08-19'],
    ['id' => 4, 'title' => 'جلسه ۱۲: معادله درجه دوم', 'course_id' => '1', 'class_id' => '1', 'duration' => 0, 'size' => '—', 'access' => 'paid', 'spot' => 1, 'status' => 'processing', 'progress' => 64, 'date' => '1405-08-25'],
  ];
@endphp

<x-admin.page-head tone="edu" icon="video" title="ویدیوهای ضبط‌شده" desc="کتابخانه همه ضبط کلاس‌ها و دوره‌ها؛ ویدیوها با اسپات‌پلیر محافظت می‌شوند">
  <x-admin.btn icon="up" data-modal-open="rec-modal">بارگذاری ویدیو</x-admin.btn>
</x-admin.page-head>

<div class="adm-stats">
  <x-admin.stat tone="edu" icon="video" label="کل ویدیوها" value="214"/>
  <x-admin.stat tone="edu" icon="clock" label="مدت کل" value="186 ساعت"/>
  <x-admin.stat tone="edu" icon="refresh" label="در حال پردازش" value="1"/>
  <x-admin.stat tone="edu" icon="box" label="حجم مصرفی" value="212 GB"/>
</div>

<x-admin.panel flush data-filter data-tone="edu">
  <x-admin.toolbar placeholder="جستجوی عنوان ویدیو…">
    <x-admin.filter-select key="course" placeholder="همه دوره‌ها" :options="$courses"/>
    <div class="adm-toolbar-end"><x-admin.seg :options="['ready' => 'آماده', 'processing' => 'در حال پردازش']"/></div>
  </x-admin.toolbar>

  <div class="adm-table-wrap"><table class="adm-table">
    <thead><tr><th>ویدیو</th><th>دوره / کلاس</th><th>مدت و حجم</th><th>دسترسی</th><th>وضعیت</th><th></th></tr></thead>
    <tbody data-filter-list>
      @foreach ($rows as $r)
        <tr data-name="{{ $r['title'] }}" data-state="{{ $r['status'] }}" data-course="{{ $r['course_id'] }}">
          <td><div class="adm-person"><span class="adm-lesson-ico {{ $r['access'] === 'paid' ? 'is-paid' : '' }}"><x-admin.icon :name="$r['access'] === 'paid' ? 'lock' : 'video'"/></span><div><b>{{ $r['title'] }}</b><small class="adm-num">{{ $r['date'] }}</small></div></div></td>
          <td>{{ $courses[$r['course_id']] }}<small>{{ $classes[$r['class_id']] }}</small></td>
          <td class="adm-num">@if ($r['duration']){{ $r['duration'] }} دقیقه<small>{{ $r['size'] }}</small>@else—@endif</td>
          <td class="is-nowrap">@if ($r['access'] === 'paid')<x-admin.badge tone="info">با مجوز ویدیو</x-admin.badge>@else<x-admin.badge tone="ok">پیش‌نمایش رایگان</x-admin.badge>@endif</td>
          <td style="min-width:150px">
            @if ($r['status'] === 'ready')<x-admin.badge tone="ok">آماده پخش</x-admin.badge>
            @else<div class="adm-progress" data-tone="edu"><i style="width:{{ $r['progress'] }}%"></i></div><small class="adm-num">در حال پردازش {{ $r['progress'] }}٪</small>@endif
          </td>
          <td><x-admin.row-actions edit="rec-modal" del="rec-del" :row="$r" :name="$r['title']" :url="url('admin/recordings/'.$r['id'])" edit-title="ویرایش ویدیو"/></td>
        </tr>
      @endforeach
    </tbody>
  </table></div>
  <x-admin.empty data-empty class="adm-hide" text="ویدیویی پیدا نشد."/>
  <x-slot:footer><x-admin.pager :current="1" :total="214" :per="4"/></x-slot:footer>
</x-admin.panel>
@endsection

@push('modals')
<x-admin.modal id="rec-modal" title="بارگذاری ویدیو" size="lg" :action="url('admin/recordings')">
  <x-admin.input label="عنوان ویدیو" name="title" wide required/>
  <x-admin.select label="دوره" name="course_id" :options="$courses" required/>
  <x-admin.select label="کلاس" name="class_id" :options="$classes"/>
  <x-admin.file label="فایل ویدیو" name="video" accept="video/*" icon="video" hint="MP4 یا MKV؛ بارگذاری تکه‌ای انجام می‌شود"/>
  <x-admin.select label="درس مرتبط در دوره" name="lesson_id" :options="['' => 'بعداً وصل می‌شود', '11' => 'فصل ۱ — مقدمه', '12' => 'فصل ۱ — الگوهای عددی']"/>
  <x-admin.chips label="چه کسی می‌تواند ببیند؟" name="access" :multiple="false" :options="['paid' => 'دارندگان مجوز «ویدیو»', 'free' => 'پیش‌نمایش رایگان']" :checked="['paid']"/>
  <x-admin.switch label="محافظت با اسپات‌پلیر" name="spot" :checked="true" hint="جلوگیری از دانلود و ضبط صفحه"/>
  <x-admin.textarea label="توضیحات (اختیاری)" name="description" rows="3"/>
</x-admin.modal>

<x-admin.confirm id="rec-del" title="حذف ویدیو" text="ویدیو از کتابخانه و پنل دانش‌آموزان حذف می‌شود."/>
@endpush
