@extends('admin.layouts.app')
@section('title', 'کلاس‌های آنلاین')

@section('content')
@php
  $classes = ['1' => 'ریاضی نهم — گروه الف', '2' => 'فیزیک دهم — گروه ب', '3' => 'زیست یازدهم — گروه ج'];
  $teachers = ['1' => 'دکتر محمدی', '2' => 'خانم صادقی', '3' => 'آقای نوری'];
  $platforms = ['skyroom' => 'اسکای‌روم', 'adobe' => 'ادوبی کانکت', 'meet' => 'گوگل میت', 'zoom' => 'زوم', 'bbb' => 'بیگ‌بلوباتن', 'other' => 'سایر'];
  $status = ['scheduled' => ['info', 'برنامه‌ریزی‌شده'], 'live' => ['bad', 'در حال برگزاری'], 'done' => ['mute', 'برگزارشده'], 'canceled' => ['warn', 'لغوشده']];
  $rows = $sessions ?? [
    ['id' => 1, 'title' => 'جلسه ۱۲: معادله درجه دوم', 'class_id' => '1', 'platform' => 'skyroom', 'link' => 'https://www.skyroom.online/ch/sheikhan/math9a', 'date' => '1405-08-25', 'time' => '16:00', 'duration' => 90, 'repeat' => 'once', 'access' => 'free', 'open_before' => '10', 'status' => 'live'],
    ['id' => 2, 'title' => 'جلسه ۸: قانون نیوتن', 'class_id' => '2', 'platform' => 'adobe', 'link' => 'https://connect.sheikhan.ir/physics10b', 'date' => '1405-08-25', 'time' => '17:30', 'duration' => 75, 'repeat' => 'weekly', 'access' => 'paid', 'price' => 150000, 'open_before' => '15', 'status' => 'scheduled'],
    ['id' => 3, 'title' => 'جلسه ۵: ژنتیک', 'class_id' => '3', 'platform' => 'meet', 'link' => 'https://meet.google.com/abc-defg-hij', 'date' => '1405-08-27', 'time' => '15:00', 'duration' => 60, 'repeat' => 'once', 'access' => 'free', 'open_before' => '10', 'status' => 'scheduled'],
    ['id' => 4, 'title' => 'جلسه ۱۱: نسبت و تناسب', 'class_id' => '1', 'platform' => 'skyroom', 'link' => 'https://www.skyroom.online/ch/sheikhan/math9a', 'date' => '1405-08-23', 'time' => '16:00', 'duration' => 90, 'repeat' => 'once', 'recording' => 1, 'open_before' => '10', 'status' => 'done'],
  ];
@endphp

<x-admin.page-head tone="edu" icon="live" title="کلاس‌های آنلاین" desc="هر جلسه زیرمجموعه یک کلاس است؛ دکمه ورود فقط برای اعضای کلاس با مجوز «کلاس زنده» سر ساعت فعال می‌شود">
  <x-admin.btn icon="plus" data-modal-open="session-modal">جلسه جدید</x-admin.btn>
</x-admin.page-head>

<div class="adm-stats">
  <x-admin.stat tone="edu" icon="live" label="امروز" value="3" hint="۱ در حال برگزاری"/>
  <x-admin.stat tone="edu" icon="cal" label="این هفته" value="14"/>
  <x-admin.stat tone="edu" icon="refresh" label="جلسات تکرارشونده" value="9"/>
  <x-admin.stat tone="edu" icon="lock" label="جلسات پولی" value="4"/>
</div>

<x-admin.panel flush data-filter data-tone="edu">
  <x-admin.toolbar placeholder="جستجوی جلسه یا کلاس…">
    <x-admin.filter-select key="class" placeholder="همه کلاس‌ها" :options="$classes"/>
    <div class="adm-toolbar-end"><x-admin.seg :options="['scheduled' => 'برنامه‌ریزی‌شده', 'live' => 'زنده', 'done' => 'برگزارشده']"/></div>
  </x-admin.toolbar>

  <div class="adm-table-wrap"><table class="adm-table">
    <thead><tr><th>جلسه</th><th>زمان</th><th>پلتفرم و لینک ورود</th><th>فایل ضبط‌شده</th><th>وضعیت</th><th></th></tr></thead>
    <tbody data-filter-list>
      @foreach ($rows as $r)
        <tr data-name="{{ $r['title'].' '.$classes[$r['class_id']] }}" data-state="{{ $r['status'] }}" data-class="{{ $r['class_id'] }}">
          <td><b>{{ $r['title'] }}</b><small>{{ $classes[$r['class_id']] }} · {{ $teachers[$r['class_id']] }}</small></td>
          <td class="is-nowrap adm-num"><b>{{ $r['time'] }}</b> · {{ $r['duration'] }} دقیقه<small>{{ $r['date'] }}@if ($r['repeat'] === 'weekly') · هر هفته @endif</small></td>
          <td>
            <small style="display:inline;margin-inline-end:8px">{{ $platforms[$r['platform']] }}</small>
            <button type="button" class="adm-copy" data-copy="{{ $r['link'] }}" title="کپی لینک"><x-admin.icon name="copy"/><span>{{ $r['link'] }}</span></button>
          </td>
          <td>@if (!empty($r['recording']))<x-admin.badge tone="ok">موجود</x-admin.badge>@else<x-admin.badge tone="mute">ندارد</x-admin.badge>@endif</td>
          <td><x-admin.badge :tone="$status[$r['status']][0]">{{ $status[$r['status']][1] }}</x-admin.badge></td>
          <td>
            <x-admin.row-actions edit="session-modal" del="session-del" :row="$r" :name="$r['title']" :url="url('admin/online-classes/'.$r['id'])" edit-title="ویرایش جلسه">
              <x-admin.btn variant="ok" size="sm" icon="link" :href="$r['link']" target="_blank" rel="noopener">ورود</x-admin.btn>
            </x-admin.row-actions>
          </td>
        </tr>
      @endforeach
    </tbody>
  </table></div>
  <x-admin.empty data-empty class="adm-hide" text="جلسه‌ای پیدا نشد."/>
  <x-slot:footer><x-admin.pager :current="1" :total="64" :per="4"/></x-slot:footer>
</x-admin.panel>
@endsection

@push('modals')
<x-admin.modal id="session-modal" title="جلسه آنلاین جدید" size="lg" :action="url('admin/online-classes')">
  <x-admin.legend title="اطلاعات جلسه"/>
  <x-admin.input label="عنوان جلسه" name="title" wide required/>
  <x-admin.select label="کلاس" name="class_id" :options="$classes" required/>
  <x-admin.select label="پلتفرم" name="platform" :options="$platforms"/>
  <x-admin.input label="لینک ورود به کلاس" name="link" type="url" placeholder="https://" wide required hint="فقط دانش‌آموزان همین کلاس، سر ساعت این لینک را می‌بینند"/>

  <x-admin.legend title="زمان‌بندی"/>
  <x-admin.input label="تاریخ" name="date" placeholder="۱۴۰۵/۰۸/۲۵" required/>
  <x-admin.input label="ساعت شروع" name="time" type="time" required/>
  <x-admin.input label="مدت" name="duration" type="number" min="15" step="5" addon="دقیقه"/>
  <x-admin.select label="فعال شدن دکمه ورود" name="open_before" :options="['5' => '۵ دقیقه قبل', '10' => '۱۰ دقیقه قبل', '15' => '۱۵ دقیقه قبل', '30' => '۳۰ دقیقه قبل']"/>
  <x-admin.select label="تکرار" name="repeat" :options="['once' => 'یک‌بار', 'weekly' => 'هر هفته در همین روز', 'class' => 'طبق روزهای کلاس']"/>
  <div data-show-if="repeat=weekly|class"><x-admin.input label="تکرار تا تاریخ" name="repeat_until" placeholder="۱۴۰۵/۱۲/۲۹"/></div>

  <x-admin.legend title="دسترسی و اطلاع‌رسانی"/>
  <x-admin.select label="فایل ضبط‌شده جلسه" name="recording_id" :options="['' => 'هنوز ضبط نشده', '1' => 'جلسه ۱۱: نسبت و تناسب', '2' => 'جلسه ۷: حرکت شتاب‌دار']" hint="پس از برگزاری، ضبط به همین جلسه و دوره وصل می‌شود"/>
  <x-admin.select label="وضعیت" name="status" :options="['scheduled' => 'برنامه‌ریزی‌شده', 'done' => 'برگزارشده', 'canceled' => 'لغوشده']"/>
  <x-admin.switch label="اطلاع‌رسانی به دانش‌آموزان و اولیا" name="notify" :checked="true" hint="پیام در پنل و پیامک" wide/>
</x-admin.modal>

<x-admin.confirm id="session-del" title="حذف جلسه" text="جلسه و لینک ورود آن از پنل دانش‌آموزان حذف می‌شود."/>
@endpush
