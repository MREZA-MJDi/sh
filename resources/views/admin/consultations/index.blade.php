@extends('admin.layouts.app')
@section('title', 'مشاوره‌ها')

@section('content')
@php
  $consultants = ['1' => 'آقای شیخان', '2' => 'خانم رحیمی (مشاور تحصیلی)'];
  $status = ['pending' => ['warn', 'در انتظار بررسی'], 'booked' => ['info', 'نوبت داده شد'], 'done' => ['ok', 'انجام‌شده'], 'rejected' => ['mute', 'رد شد']];
  $rows = $requests ?? [
    ['id' => 1, 'who' => 'مریم حسینی', 'role' => 'ولی', 'student' => 'علی رضایی', 'topic' => 'برنامه‌ریزی مطالعه', 'wish' => 'پنجشنبه عصر', 'status' => 'pending', 'consultant_id' => '1', 'date' => '', 'time' => ''],
    ['id' => 2, 'who' => 'دکتر محمدی', 'role' => 'مدرس', 'student' => 'سارا احمدی', 'topic' => 'کاهش تمرکز در کلاس', 'wish' => 'هر روز بعد از ۱۸', 'status' => 'pending', 'consultant_id' => '2', 'date' => '', 'time' => ''],
    ['id' => 3, 'who' => 'زهرا کریمی', 'role' => 'ولی', 'student' => 'نگار کریمی', 'topic' => 'انتخاب رشته', 'wish' => 'سه‌شنبه صبح', 'status' => 'booked', 'consultant_id' => '1', 'date' => '1405-08-28', 'time' => '10:00'],
  ];
@endphp

<x-admin.page-head tone="comm" icon="chat" title="مشاوره‌ها" desc="درخواست مشاوره اولیا و مدرسان را بررسی و نوبت‌دهی کنید">
  <x-admin.badge tone="info" class="is-plain">فاز توسعه</x-admin.badge>
  <x-admin.btn icon="plus" data-modal-open="consult-modal">ثبت نوبت</x-admin.btn>
</x-admin.page-head>

<div class="adm-stats">
  <x-admin.stat tone="comm" icon="chat" label="درخواست جدید" value="2"/>
  <x-admin.stat tone="comm" icon="cal" label="نوبت‌های هفته" value="5"/>
  <x-admin.stat tone="comm" icon="check" label="انجام‌شده این ماه" value="11"/>
  <x-admin.stat tone="comm" icon="clock" label="میانگین انتظار" value="۲ روز"/>
</div>

<div class="adm-split">
  <x-admin.panel flush data-filter data-tone="comm">
    <x-admin.toolbar placeholder="جستجوی درخواست‌کننده یا موضوع…">
      <div class="adm-toolbar-end"><x-admin.seg :options="['pending' => 'منتظر', 'booked' => 'نوبت‌دار', 'done' => 'انجام‌شده']"/></div>
    </x-admin.toolbar>
    <div class="adm-table-wrap"><table class="adm-table">
      <thead><tr><th>درخواست‌کننده</th><th>موضوع</th><th>زمان دلخواه</th><th>وضعیت</th><th></th></tr></thead>
      <tbody data-filter-list>
        @foreach ($rows as $r)
          <tr data-name="{{ $r['who'].' '.$r['student'].' '.$r['topic'] }}" data-state="{{ $r['status'] }}">
            <td><x-admin.person :name="$r['who']" :sub="$r['role'].' · '.$r['student']"/></td>
            <td>{{ $r['topic'] }}</td>
            <td>{{ $r['wish'] }}</td>
            <td><x-admin.badge :tone="$status[$r['status']][0]">{{ $status[$r['status']][1] }}</x-admin.badge>@if ($r['date'])<small class="adm-num">{{ $r['date'] }} · {{ $r['time'] }}</small>@endif</td>
            <td>
              <x-admin.row-actions del="consult-del" :name="$r['topic']" :url="url('admin/consultations/'.$r['id'])">
                <x-admin.btn size="sm" icon="cal" data-modal-open="consult-modal" data-title="نوبت‌دهی مشاوره" :data-fill="json_encode($r, JSON_UNESCAPED_UNICODE)" :data-action="url('admin/consultations/'.$r['id'])" data-method="PUT">{{ $r['status'] === 'pending' ? 'نوبت‌دهی' : 'ویرایش' }}</x-admin.btn>
              </x-admin.row-actions>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table></div>
    <x-admin.empty data-empty class="adm-hide" text="درخواستی پیدا نشد."/>
    <x-slot:footer><x-admin.pager :current="1" :total="13" :per="3"/></x-slot:footer>
  </x-admin.panel>

  <x-admin.panel title="نوبت‌های پیش‌رو" flush data-tone="comm">
    <div class="adm-side-list">
      <div><div class="adm-session-time adm-num" data-tone="comm" style="width:58px">۱۰:۰۰<small>۲۸ آبان</small></div><div class="adm-grow"><b>زهرا کریمی</b><div class="adm-muted">انتخاب رشته · آقای شیخان</div></div></div>
      <div><div class="adm-session-time adm-num" data-tone="comm" style="width:58px">۱۶:۳۰<small>۲۹ آبان</small></div><div class="adm-grow"><b>حسین احمدی</b><div class="adm-muted">آمادگی امتحانات · خانم رحیمی</div></div></div>
    </div>
  </x-admin.panel>
</div>
@endsection

@push('modals')
<x-admin.modal id="consult-modal" title="ثبت نوبت مشاوره" size="lg" :action="url('admin/consultations')">
  <x-admin.input label="درخواست‌کننده" name="who" required/>
  <x-admin.input label="دانش‌آموز" name="student"/>
  <x-admin.input label="موضوع مشاوره" name="topic" wide required/>
  <x-admin.legend title="نوبت"/>
  <x-admin.select label="مشاور" name="consultant_id" :options="$consultants"/>
  <x-admin.select label="وضعیت" name="status" :options="['pending' => 'در انتظار بررسی', 'booked' => 'نوبت داده شد', 'done' => 'انجام‌شده', 'rejected' => 'رد شد']"/>
  <x-admin.input label="تاریخ" name="date" placeholder="۱۴۰۵/۰۸/۲۸"/>
  <x-admin.input label="ساعت" name="time" type="time"/>
  <x-admin.switch label="اطلاع‌رسانی به درخواست‌کننده" name="notify" :checked="true" wide/>
</x-admin.modal>

<x-admin.confirm id="consult-del" title="حذف درخواست" text="درخواست مشاوره حذف می‌شود."/>
@endpush
