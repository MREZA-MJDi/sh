@extends('admin.layouts.app')
@section('title', 'اولیا')

@section('content')
@php
  $kids = ['1' => 'سارا احمدی', '2' => 'علی رضایی', '3' => 'نگار کریمی', '4' => 'رضا کاظمی'];
  $relations = ['father' => 'پدر', 'mother' => 'مادر', 'guardian' => 'قیم / سایر'];
  $rows = $parents ?? [
    ['id' => 1, 'name' => 'مریم حسینی', 'mobile' => '09123334455', 'email' => 'maryam@example.com', 'relation' => 'mother', 'children' => ['2'], 'is_active' => 1],
    ['id' => 2, 'name' => 'حسین احمدی', 'mobile' => '09121239876', 'email' => '', 'relation' => 'father', 'children' => ['1'], 'is_active' => 1],
    ['id' => 3, 'name' => 'زهرا کریمی', 'mobile' => '09127778899', 'email' => 'zahra@example.com', 'relation' => 'mother', 'children' => ['3'], 'is_active' => 1],
    ['id' => 4, 'name' => 'اکبر کاظمی', 'mobile' => '09125551122', 'email' => '', 'relation' => 'father', 'children' => [], 'is_active' => 0],
  ];
@endphp

<x-admin.page-head tone="users" icon="family" title="اولیا" desc="ثبت ولی، اتصال به فرزند و ساخت حساب ورود به پنل اولیا">
  <x-admin.btn icon="plus" data-modal-open="parent-modal">ولی جدید</x-admin.btn>
</x-admin.page-head>

<div class="adm-stats">
  <x-admin.stat tone="users" icon="family" label="کل اولیا" value="1,102" hint="۱,۰۷۰ فعال"/>
  <x-admin.stat tone="users" icon="user" label="دارای بیش از یک فرزند" value="148"/>
  <x-admin.stat tone="users" icon="alert" label="بدون فرزند متصل" value="12"/>
  <x-admin.stat tone="comm" icon="msg" label="پیام‌های بی‌پاسخ" value="5"/>
</div>

<x-admin.panel flush data-filter data-tone="users">
  <x-admin.toolbar placeholder="جستجوی نام ولی، موبایل یا نام فرزند…">
    <x-admin.filter-select key="relation" placeholder="همه نسبت‌ها" :options="$relations"/>
    <div class="adm-toolbar-end"><x-admin.seg :options="['on' => 'فعال', 'off' => 'غیرفعال']"/></div>
  </x-admin.toolbar>

  <div class="adm-table-wrap"><table class="adm-table">
    <thead><tr><th>ولی</th><th>نسبت</th><th>فرزندان</th><th>وضعیت</th><th></th></tr></thead>
    <tbody data-filter-list>
      @foreach ($rows as $r)
        <tr data-name="{{ $r['name'].' '.$r['mobile'].' '.collect($r['children'])->map(fn ($c) => $kids[$c])->join(' ') }}" data-state="{{ $r['is_active'] ? 'on' : 'off' }}" data-relation="{{ $r['relation'] }}">
          <td><x-admin.person :name="$r['name']" :sub="$r['mobile']"/></td>
          <td>{{ $relations[$r['relation']] }}</td>
          <td>
            @forelse ($r['children'] as $c)<x-admin.badge tone="tone" class="is-plain" data-tone="users">{{ $kids[$c] }}</x-admin.badge> @empty<x-admin.badge tone="warn">بدون فرزند</x-admin.badge>@endforelse
          </td>
          <td>@if ($r['is_active'])<x-admin.badge tone="ok">فعال</x-admin.badge>@else<x-admin.badge tone="mute">غیرفعال</x-admin.badge>@endif</td>
          <td><x-admin.row-actions edit="parent-modal" del="parent-del" :row="$r" :name="$r['name']" :url="url('admin/parents/'.$r['id'])" edit-title="ویرایش ولی"/></td>
        </tr>
      @endforeach
    </tbody>
  </table></div>
  <x-admin.empty data-empty class="adm-hide" text="ولی‌ای پیدا نشد."/>
  <x-slot:footer><x-admin.pager :current="1" :total="1102" :per="4"/></x-slot:footer>
</x-admin.panel>
@endsection

@push('modals')
<x-admin.modal id="parent-modal" title="ولی جدید" size="lg" :action="url('admin/parents')">
  <x-admin.legend title="مشخصات"/>
  <x-admin.input label="نام و نام خانوادگی" name="name" required/>
  <x-admin.select label="نسبت با دانش‌آموز" name="relation" :options="$relations" required/>
  <x-admin.input label="موبایل" name="mobile" type="tel" required hint="نام کاربری ورود همین شماره است"/>
  <x-admin.input label="ایمیل" name="email" type="email"/>

  <x-admin.legend title="فرزندان" desc="ولی فقط نتایج فرزندان متصل را می‌بیند"/>
  <x-admin.chips label="دانش‌آموزان" name="children" :options="$kids" scroll/>

  <x-admin.legend title="حساب کاربری"/>
  <x-admin.input label="رمز عبور" name="password" type="password" hint="هنگام ویرایش، خالی بگذارید تا تغییر نکند"/>
  <x-admin.switch label="حساب فعال باشد" name="is_active" :checked="true"/>
  <x-admin.switch label="ارسال پیامک اطلاعات ورود" name="send_sms" wide/>
</x-admin.modal>

<x-admin.confirm id="parent-del" title="حذف ولی" text="حساب ولی حذف می‌شود؛ دانش‌آموزان متصل بدون ولی می‌مانند."/>
@endpush
