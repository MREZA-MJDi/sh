@extends('admin.layouts.app')
@section('title', 'مدرسان')

@section('content')
@php
  $courses = ['1' => 'ریاضی نهم', '2' => 'فیزیک دهم', '3' => 'زیست یازدهم', '4' => 'شیمی یازدهم'];
  $subjects = ['math' => 'ریاضی', 'physics' => 'فیزیک', 'chem' => 'شیمی', 'bio' => 'زیست‌شناسی', 'lit' => 'ادبیات', 'eng' => 'زبان انگلیسی'];
  $rows = $teachers ?? [
    ['id' => 1, 'name' => 'دکتر محمدی', 'mobile' => '09121110001', 'email' => 'm.mohammadi@example.com', 'subject' => 'math', 'bio' => 'مدرس ریاضی با ۱۲ سال سابقه تدریس.', 'courses' => ['1'], 'is_active' => 1, 'classes' => 4],
    ['id' => 2, 'name' => 'خانم صادقی', 'mobile' => '09121110002', 'email' => 'sadeghi@example.com', 'subject' => 'physics', 'bio' => '', 'courses' => ['2'], 'is_active' => 1, 'classes' => 3],
    ['id' => 3, 'name' => 'آقای نوری', 'mobile' => '09121110003', 'email' => 'nouri@example.com', 'subject' => 'bio', 'bio' => '', 'courses' => ['3'], 'is_active' => 1, 'classes' => 2],
    ['id' => 4, 'name' => 'آقای کریمی', 'mobile' => '09121110004', 'email' => '', 'subject' => 'chem', 'bio' => '', 'courses' => ['4'], 'is_active' => 0, 'classes' => 0],
  ];
@endphp

<x-admin.page-head tone="users" icon="teacher" title="مدرسان" desc="افزودن مدرس و ساخت حساب ورود به پنل مدرس">
  <x-admin.btn icon="plus" data-modal-open="teacher-modal">مدرس جدید</x-admin.btn>
</x-admin.page-head>

<div class="adm-stats">
  <x-admin.stat tone="users" icon="teacher" label="کل مدرسان" value="14" hint="۱۳ فعال"/>
  <x-admin.stat tone="users" icon="book" label="دوره‌های در حال تدریس" value="18"/>
  <x-admin.stat tone="users" icon="cls" label="کلاس‌های فعال" value="24"/>
  <x-admin.stat tone="users" icon="alert" label="بدون کلاس" value="1"/>
</div>

<x-admin.panel flush data-filter data-tone="users">
  <x-admin.toolbar placeholder="جستجوی نام یا موبایل مدرس…">
    <x-admin.filter-select key="subject" placeholder="همه تخصص‌ها" :options="$subjects"/>
    <div class="adm-toolbar-end"><x-admin.seg :options="['on' => 'فعال', 'off' => 'غیرفعال']"/></div>
  </x-admin.toolbar>

  <div class="adm-table-wrap"><table class="adm-table">
    <thead><tr><th>مدرس</th><th>تخصص</th><th>دوره‌ها</th><th>کلاس‌ها</th><th>وضعیت</th><th></th></tr></thead>
    <tbody data-filter-list>
      @foreach ($rows as $r)
        <tr data-name="{{ $r['name'].' '.$r['mobile'] }}" data-state="{{ $r['is_active'] ? 'on' : 'off' }}" data-subject="{{ $r['subject'] }}">
          <td><x-admin.person :name="$r['name']" :sub="$r['mobile']"/></td>
          <td>{{ $subjects[$r['subject']] }}</td>
          <td>{{ collect($r['courses'])->map(fn ($c) => $courses[$c])->join('، ') ?: '—' }}</td>
          <td class="adm-num">{{ $r['classes'] }}</td>
          <td>@if ($r['is_active'])<x-admin.badge tone="ok">فعال</x-admin.badge>@else<x-admin.badge tone="mute">غیرفعال</x-admin.badge>@endif</td>
          <td><x-admin.row-actions edit="teacher-modal" del="teacher-del" :row="$r" :name="$r['name']" :url="url('admin/teachers/'.$r['id'])" edit-title="ویرایش مدرس"/></td>
        </tr>
      @endforeach
    </tbody>
  </table></div>
  <x-admin.empty data-empty class="adm-hide" text="مدرسی پیدا نشد."/>
  <x-slot:footer><x-admin.pager :current="1" :total="14" :per="4"/></x-slot:footer>
</x-admin.panel>
@endsection

@push('modals')
<x-admin.modal id="teacher-modal" title="مدرس جدید" size="lg" :action="url('admin/teachers')">
  <x-admin.legend title="مشخصات"/>
  <x-admin.input label="نام و نام خانوادگی" name="name" required/>
  <x-admin.select label="تخصص" name="subject" :options="$subjects" placeholder="انتخاب تخصص" required/>
  <x-admin.input label="موبایل" name="mobile" type="tel" required hint="نام کاربری ورود همین شماره است"/>
  <x-admin.input label="ایمیل" name="email" type="email"/>
  <x-admin.textarea label="معرفی کوتاه" name="bio" rows="3" hint="در صفحه دوره‌ها به دانش‌آموزان نمایش داده می‌شود"/>
  <x-admin.file label="عکس پروفایل" name="avatar" accept="image/*" hint="JPG یا PNG، حداکثر ۲ مگابایت" icon="user"/>

  <x-admin.legend title="دوره‌های قابل تدریس" desc="مدرس فقط به محتوای همین دوره‌ها دسترسی دارد"/>
  <x-admin.chips label="دوره‌ها" name="courses" :options="$courses"/>

  <x-admin.legend title="حساب کاربری"/>
  <x-admin.input label="رمز عبور" name="password" type="password" hint="هنگام ویرایش، خالی بگذارید تا تغییر نکند"/>
  <x-admin.switch label="حساب فعال باشد" name="is_active" :checked="true"/>
  <x-admin.switch label="ارسال پیامک اطلاعات ورود" name="send_sms" wide/>
</x-admin.modal>

<x-admin.confirm id="teacher-del" title="حذف مدرس" text="حساب مدرس حذف می‌شود؛ دوره‌ها و کلاس‌هایش بدون مدرس می‌مانند."/>
@endpush
