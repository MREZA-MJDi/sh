@extends('admin.layouts.app')
@section('title', 'اعلان‌ها')

@section('content')
@php
  $classes = ['1' => 'ریاضی نهم — الف', '2' => 'فیزیک دهم — ب', '3' => 'زیست یازدهم — ج'];
  $audience = ['all' => 'همه کاربران', 'student' => 'دانش‌آموزان', 'teacher' => 'مدرسان', 'parent' => 'اولیا', 'class' => 'یک کلاس'];
  $status = ['published' => ['ok', 'منتشرشده'], 'scheduled' => ['info', 'زمان‌بندی‌شده'], 'draft' => ['warn', 'پیش‌نویس']];
  $rows = $announcements ?? [
    ['id' => 1, 'title' => 'تعطیلی کلاس‌ها در روز چهارشنبه', 'body' => 'به دلیل برگزاری آزمون سراسری، کلاس‌های چهارشنبه تشکیل نمی‌شود.', 'audience' => 'all', 'class_id' => '', 'channels' => ['panel', 'sms'], 'publish_at' => '1405-08-24', 'status' => 'published'],
    ['id' => 2, 'title' => 'کلاس رفع اشکال ریاضی', 'body' => 'جلسه رفع اشکال پنجشنبه ساعت ۱۷ برگزار می‌شود.', 'audience' => 'class', 'class_id' => '1', 'channels' => ['panel'], 'publish_at' => '1405-08-26', 'status' => 'scheduled'],
    ['id' => 3, 'title' => 'جلسه اولیا — آذرماه', 'body' => '', 'audience' => 'parent', 'class_id' => '', 'channels' => ['panel', 'sms'], 'publish_at' => '', 'status' => 'draft'],
  ];
@endphp

<x-admin.page-head tone="comm" icon="bell" title="اعلان‌ها" desc="اطلاعیه را برای همه یا یک گروه مشخص در پنل و پیامک ارسال کنید">
  <x-admin.btn icon="plus" data-modal-open="ann-modal">اعلان جدید</x-admin.btn>
</x-admin.page-head>

<x-admin.panel flush data-filter data-tone="comm">
  <x-admin.toolbar placeholder="جستجوی عنوان اعلان…">
    <x-admin.filter-select key="audience" placeholder="همه مخاطبان" :options="$audience"/>
    <div class="adm-toolbar-end"><x-admin.seg :options="['published' => 'منتشرشده', 'scheduled' => 'زمان‌بندی‌شده', 'draft' => 'پیش‌نویس']"/></div>
  </x-admin.toolbar>
  <div class="adm-table-wrap"><table class="adm-table">
    <thead><tr><th>اعلان</th><th>مخاطب</th><th>کانال</th><th>انتشار</th><th>وضعیت</th><th></th></tr></thead>
    <tbody data-filter-list>
      @foreach ($rows as $r)
        <tr data-name="{{ $r['title'] }}" data-state="{{ $r['status'] }}" data-audience="{{ $r['audience'] }}">
          <td><b>{{ $r['title'] }}</b>@if ($r['body'])<div class="adm-msg-snippet">{{ $r['body'] }}</div>@endif</td>
          <td>{{ $audience[$r['audience']] }}@if ($r['class_id'])<small>{{ $classes[$r['class_id']] }}</small>@endif</td>
          <td>{{ collect($r['channels'])->map(fn ($c) => $c === 'sms' ? 'پیامک' : 'پنل')->join(' + ') }}</td>
          <td class="adm-num">{{ $r['publish_at'] ?: '—' }}</td>
          <td><x-admin.badge :tone="$status[$r['status']][0]">{{ $status[$r['status']][1] }}</x-admin.badge></td>
          <td><x-admin.row-actions edit="ann-modal" del="ann-del" :row="$r" :name="$r['title']" :url="url('admin/announcements/'.$r['id'])" edit-title="ویرایش اعلان"/></td>
        </tr>
      @endforeach
    </tbody>
  </table></div>
  <x-admin.empty data-empty class="adm-hide" text="اعلانی پیدا نشد."/>
  <x-slot:footer><x-admin.pager :current="1" :total="27" :per="3"/></x-slot:footer>
</x-admin.panel>
@endsection

@push('modals')
<x-admin.modal id="ann-modal" title="اعلان جدید" size="lg" :action="url('admin/announcements')">
  <x-admin.input label="عنوان" name="title" wide required/>
  <x-admin.textarea label="متن اعلان" name="body" rows="5" required/>
  <x-admin.legend title="مخاطب و انتشار"/>
  <x-admin.select label="مخاطب" name="audience" :options="$audience"/>
  <div data-show-if="audience=class"><x-admin.select label="کلاس" name="class_id" :options="$classes"/></div>
  <x-admin.chips label="ارسال از طریق" name="channels" :options="['panel' => 'اعلان در پنل', 'sms' => 'پیامک']" :checked="['panel']" :wide="false"/>
  <x-admin.input label="تاریخ انتشار" name="publish_at" placeholder="خالی = همین حالا"/>
  <x-admin.select label="وضعیت" name="status" :options="['published' => 'منتشر شود', 'scheduled' => 'زمان‌بندی‌شده', 'draft' => 'پیش‌نویس']"/>
</x-admin.modal>

<x-admin.confirm id="ann-del" title="حذف اعلان" text="اعلان از پنل کاربران هم حذف می‌شود."/>
@endpush
