@extends('admin.layouts.app')
@section('title', 'ثبت‌نام‌ها و مجوزها')

@section('content')
@php
  $students = ['1' => 'سارا احمدی', '2' => 'علی رضایی', '3' => 'نگار کریمی', '4' => 'رضا مرادی'];
  $courses = ['1' => 'ریاضی تکمیلی دهم', '2' => 'فیزیک دهم', '3' => 'زیست یازدهم'];
  $plans = ['1' => 'پایه', '2' => 'پیشرفته', '3' => 'ویژه', '4' => 'فقط کلاس زنده'];
  $cohorts = ['' => 'بدون گروه', '1' => 'گروه شنبه‌ها — استاد احمدی', '2' => 'گروه دوشنبه‌ها — استاد رضایی'];
  $includes = ['video' => 'ویدیوهای ضبط‌شده', 'live' => 'کلاس زنده', 'resources' => 'جزوه و منابع', 'exams' => 'آزمون و تکلیف', 'qa' => 'پرسش و پاسخ'];
  $status = ['active' => ['ok', 'فعال'], 'expiring' => ['warn', 'در حال انقضا'], 'expired' => ['mute', 'منقضی'], 'suspended' => ['bad', 'تعلیق‌شده']];
  $rows = $enrollments ?? [
    ['id' => 1, 'student_id' => '1', 'course_id' => '1', 'plan_id' => '2', 'cohort_id' => '1', 'access' => ['video', 'live', 'resources', 'exams'], 'expires_at' => '1406-08-25', 'status' => 'active', 'source' => 'پرداخت TX-90412', 'progress' => 35],
    ['id' => 2, 'student_id' => '2', 'course_id' => '2', 'plan_id' => '1', 'cohort_id' => '', 'access' => ['video', 'resources'], 'expires_at' => '1405-09-10', 'status' => 'expiring', 'source' => 'پرداخت TX-90311', 'progress' => 72],
    ['id' => 3, 'student_id' => '3', 'course_id' => '3', 'plan_id' => '4', 'cohort_id' => '2', 'access' => ['live'], 'expires_at' => '1406-02-01', 'status' => 'active', 'source' => 'ثبت دستی', 'progress' => 10],
    ['id' => 4, 'student_id' => '4', 'course_id' => '1', 'plan_id' => '3', 'cohort_id' => '2', 'access' => ['video', 'live', 'resources', 'exams', 'qa'], 'expires_at' => '1405-06-01', 'status' => 'expired', 'source' => 'پرداخت TX-88120', 'progress' => 100],
  ];
@endphp

<x-admin.page-head tone="shop" icon="lock" title="ثبت‌نام‌ها و مجوزها" desc="ثبت‌نام یعنی دانش‌آموز در دوره عضو است؛ مجوز مشخص می‌کند به کدام امکانات و تا کی دسترسی دارد">
  <x-admin.btn icon="plus" data-modal-open="enr-modal">ثبت‌نام دستی</x-admin.btn>
</x-admin.page-head>

<div class="adm-stats">
  <x-admin.stat tone="shop" icon="check" label="ثبت‌نام فعال" value="1,046"/>
  <x-admin.stat tone="shop" icon="clock" label="در حال انقضا (۳۰ روز)" value="38"/>
  <x-admin.stat tone="shop" icon="refresh" label="منقضی‌شده" value="112" hint="قابل تمدید"/>
  <x-admin.stat tone="shop" icon="alert" label="تعلیق / منتظر پرداخت" value="7"/>
</div>

<x-admin.panel flush data-filter data-tone="shop">
  <x-admin.toolbar placeholder="جستجوی دانش‌آموز یا دوره…">
    <x-admin.filter-select key="course" placeholder="همه دوره‌ها" :options="$courses"/>
    <div class="adm-toolbar-end"><x-admin.seg :options="['active' => 'فعال', 'expiring' => 'در حال انقضا', 'expired' => 'منقضی', 'suspended' => 'تعلیق']"/></div>
  </x-admin.toolbar>

  <div class="adm-table-wrap"><table class="adm-table">
    <thead><tr><th>دانش‌آموز</th><th>دوره و پلن</th><th>کلاس (گروه)</th><th>مجوزها</th><th>اعتبار تا</th><th>وضعیت</th><th></th></tr></thead>
    <tbody data-filter-list>
      @foreach ($rows as $r)
        <tr data-name="{{ $students[$r['student_id']].' '.$courses[$r['course_id']] }}" data-state="{{ $r['status'] }}" data-course="{{ $r['course_id'] }}">
          <td><x-admin.person :name="$students[$r['student_id']]" :sub="$r['source']"/></td>
          <td><b>{{ $courses[$r['course_id']] }}</b><small>پلن {{ $plans[$r['plan_id']] }}</small></td>
          <td>{{ $r['cohort_id'] ? $cohorts[$r['cohort_id']] : '—' }}</td>
          <td><x-admin.access-flags :flags="$r['access']"/></td>
          <td class="adm-num is-nowrap">{{ $r['expires_at'] }}</td>
          <td><x-admin.badge :tone="$status[$r['status']][0]">{{ $status[$r['status']][1] }}</x-admin.badge></td>
          <td>
            <x-admin.row-actions edit="enr-modal" del="enr-del" :row="$r" :name="$students[$r['student_id']].' — '.$courses[$r['course_id']]" :url="url('admin/enrollments/'.$r['id'])" edit-title="ویرایش ثبت‌نام و مجوز">
              @if (in_array($r['status'], ['expiring', 'expired']))<x-admin.btn variant="ok" size="sm" icon="refresh" data-toast="مجوز تمدید شد (نمایشی)">تمدید</x-admin.btn>@endif
            </x-admin.row-actions>
          </td>
        </tr>
      @endforeach
    </tbody>
  </table></div>
  <x-admin.empty data-empty class="adm-hide" text="ثبت‌نامی پیدا نشد."/>
  <x-slot:footer><x-admin.pager :current="1" :total="1196" :per="4"/></x-slot:footer>
</x-admin.panel>
@endsection

@push('modals')
<x-admin.modal id="enr-modal" title="ثبت‌نام دستی" size="lg" :action="url('admin/enrollments')">
  <x-admin.legend title="دانش‌آموز و دوره"/>
  <x-admin.select label="دانش‌آموز" name="student_id" :options="$students" required/>
  <x-admin.select label="دوره" name="course_id" :options="$courses" required/>
  <x-admin.select label="پلن" name="plan_id" :options="$plans" hint="با انتخاب پلن، مجوزهای زیر پر می‌شود و قابل تغییر است"/>
  <x-admin.select label="وضعیت" name="status" :options="['active' => 'فعال', 'suspended' => 'تعلیق‌شده', 'expired' => 'منقضی']"/>
  <x-admin.legend title="مجوزهای دسترسی"/>
  <x-admin.chips label="دسترسی به" name="access" :options="$includes"/>
  <div data-show-if="access=live"><x-admin.select label="کلاس (گروه آموزشی)" name="cohort_id" :options="$cohorts" wide/></div>
  <x-admin.input label="اعتبار تا تاریخ" name="expires_at" placeholder="۱۴۰۶/۰۸/۲۵" hint="خالی = نامحدود"/>
  <x-admin.input label="یادداشت" name="note"/>
</x-admin.modal>

<x-admin.confirm id="enr-del" title="لغو ثبت‌نام" text="ثبت‌نام و همه مجوزهای این دانش‌آموز در دوره لغو می‌شود؛ پرداخت‌ها حذف نمی‌شوند." submit="بله، لغو شود"/>
@endpush
