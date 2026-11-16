@extends('admin.layouts.app')
@section('title', 'دانش‌آموزان')

@section('content')
@php
  $grades = ['7' => 'هفتم', '8' => 'هشتم', '9' => 'نهم', '10' => 'دهم', '11' => 'یازدهم', '12' => 'دوازدهم'];
  $parents = ['1' => 'مریم حسینی', '2' => 'حسین احمدی', '3' => 'زهرا کریمی'];
  $classes = ['1' => 'ریاضی نهم — الف', '2' => 'فیزیک دهم — ب', '3' => 'زیست یازدهم — ج'];
  $rows = $students ?? [
    ['id' => 1, 'name' => 'سارا احمدی', 'mobile' => '09121234567', 'email' => 'sara@example.com', 'national_code' => '0012345678', 'grade' => '9', 'school' => 'دبیرستان فرزانگان', 'parent_id' => '2', 'classes' => ['1'], 'is_active' => 1],
    ['id' => 2, 'name' => 'علی رضایی', 'mobile' => '09352223344', 'email' => 'ali@example.com', 'national_code' => '0023456789', 'grade' => '10', 'school' => 'دبیرستان شهید بهشتی', 'parent_id' => '1', 'classes' => ['2'], 'is_active' => 1],
    ['id' => 3, 'name' => 'نگار کریمی', 'mobile' => '09124445566', 'email' => 'negar@example.com', 'national_code' => '0034567890', 'grade' => '11', 'school' => 'دبیرستان سمپاد', 'parent_id' => '3', 'classes' => ['3', '1'], 'is_active' => 1],
    ['id' => 4, 'name' => 'رضا کاظمی', 'mobile' => '09015556677', 'email' => '', 'national_code' => '0045678901', 'grade' => '9', 'school' => '', 'parent_id' => '', 'classes' => [], 'is_active' => 0],
  ];
@endphp

<x-admin.page-head tone="users" icon="user" title="دانش‌آموزان" desc="ثبت‌نام دانش‌آموز و ساخت حساب ورود به پنل دانش‌آموز">
  <x-admin.btn icon="plus" data-modal-open="student-modal">دانش‌آموز جدید</x-admin.btn>
</x-admin.page-head>

<div class="adm-stats">
  <x-admin.stat tone="users" icon="user" label="کل دانش‌آموزان" value="1,284" hint="۱,۲۴۱ فعال"/>
  <x-admin.stat tone="users" icon="plus" label="ثبت‌نام این ماه" value="32"/>
  <x-admin.stat tone="users" icon="family" label="بدون ولی" value="9" hint="نیاز به اتصال به ولی"/>
  <x-admin.stat tone="users" icon="cls" label="بدون کلاس" value="21"/>
</div>

<x-admin.panel flush data-filter data-tone="users">
  <x-admin.toolbar placeholder="جستجوی نام، موبایل یا کد ملی…">
    <x-admin.filter-select key="grade" placeholder="همه پایه‌ها" :options="$grades"/>
    <div class="adm-toolbar-end"><x-admin.seg :options="['on' => 'فعال', 'off' => 'غیرفعال']"/></div>
  </x-admin.toolbar>

  <div class="adm-table-wrap"><table class="adm-table">
    <thead><tr><th>دانش‌آموز</th><th>پایه</th><th>ولی</th><th>کلاس‌ها</th><th>وضعیت</th><th></th></tr></thead>
    <tbody data-filter-list>
      @foreach ($rows as $r)
        <tr data-name="{{ $r['name'].' '.$r['mobile'].' '.$r['national_code'] }}" data-state="{{ $r['is_active'] ? 'on' : 'off' }}" data-grade="{{ $r['grade'] }}">
          <td><x-admin.person :name="$r['name']" :sub="$r['mobile']"/></td>
          <td>{{ $grades[$r['grade']] ?? '—' }}</td>
          <td>@if ($r['parent_id']){{ $parents[$r['parent_id']] }}@else<x-admin.badge tone="warn">بدون ولی</x-admin.badge>@endif</td>
          <td class="adm-num">{{ count($r['classes']) }} کلاس</td>
          <td>@if ($r['is_active'])<x-admin.badge tone="ok">فعال</x-admin.badge>@else<x-admin.badge tone="mute">غیرفعال</x-admin.badge>@endif</td>
          <td><x-admin.row-actions edit="student-modal" del="student-del" :row="$r" :name="$r['name']" :url="url('admin/students/'.$r['id'])" edit-title="ویرایش دانش‌آموز"/></td>
        </tr>
      @endforeach
    </tbody>
  </table></div>
  <x-admin.empty data-empty class="adm-hide" text="دانش‌آموزی با این مشخصات پیدا نشد."/>
  <x-slot:footer><x-admin.pager :current="1" :total="1284" :per="4"/></x-slot:footer>
</x-admin.panel>
@endsection

@push('modals')
<x-admin.modal id="student-modal" title="دانش‌آموز جدید" size="lg" :action="url('admin/students')">
  <x-admin.legend title="مشخصات"/>
  <x-admin.input label="نام و نام خانوادگی" name="name" required/>
  <x-admin.input label="کد ملی" name="national_code" maxlength="10" ltr/>
  <x-admin.input label="موبایل" name="mobile" type="tel" required hint="نام کاربری ورود همین شماره است"/>
  <x-admin.input label="ایمیل" name="email" type="email"/>
  <x-admin.select label="پایه" name="grade" :options="$grades" placeholder="انتخاب پایه" required/>
  <x-admin.input label="مدرسه" name="school"/>
  <x-admin.file label="عکس پروفایل" name="avatar" accept="image/*" hint="JPG یا PNG، حداکثر ۲ مگابایت" icon="user"/>

  <x-admin.legend title="ارتباط‌ها" desc="ولی و کلاس‌ها بعداً هم قابل تغییر است"/>
  <x-admin.select label="ولی" name="parent_id" :options="$parents" placeholder="بدون ولی / بعداً"/>
  <x-admin.chips label="کلاس‌ها" name="classes" :options="$classes"/>

  <x-admin.legend title="حساب کاربری"/>
  <x-admin.input label="رمز عبور" name="password" type="password" hint="هنگام ویرایش، خالی بگذارید تا تغییر نکند"/>
  <x-admin.switch label="حساب فعال باشد" name="is_active" :checked="true"/>
  <x-admin.switch label="ارسال پیامک اطلاعات ورود" name="send_sms" hint="نام کاربری و رمز به موبایل دانش‌آموز ارسال می‌شود" wide/>
</x-admin.modal>

<x-admin.confirm id="student-del" title="حذف دانش‌آموز" text="حساب دانش‌آموز و سوابق حضور و تکالیفش حذف می‌شود."/>
@endpush
