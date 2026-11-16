@extends('admin.layouts.app')
@section('title', 'تکالیف')

@section('content')
@php
  $classes = ['1' => 'ریاضی نهم — الف', '2' => 'فیزیک دهم — ب', '3' => 'زیست یازدهم — ج'];
  $types = ['text' => 'متنی', 'pdf' => 'فایل PDF', 'both' => 'متن + PDF'];
  $status = ['active' => ['ok', 'فعال'], 'expired' => ['mute', 'مهلت گذشته'], 'draft' => ['warn', 'پیش‌نویس']];
  $rows = $assignments ?? [
    ['id' => 1, 'title' => 'تمرین‌های فصل ۳ — معادله‌ها', 'class_id' => '1', 'type' => 'both', 'due_date' => '1405-08-30', 'due_time' => '23:59', 'max_score' => 20, 'allow_late' => 0, 'notify' => 1, 'status' => 'active', 'done' => 14, 'total' => 22, 'body' => 'مسائل ۱ تا ۱۵ صفحه ۶۸ را حل کنید.'],
    ['id' => 2, 'title' => 'گزارش آزمایش سقوط آزاد', 'class_id' => '2', 'type' => 'pdf', 'due_date' => '1405-08-28', 'due_time' => '20:00', 'max_score' => 10, 'allow_late' => 1, 'notify' => 1, 'status' => 'active', 'done' => 9, 'total' => 18, 'body' => ''],
    ['id' => 3, 'title' => 'خلاصه‌نویسی فصل ۱', 'class_id' => '3', 'type' => 'text', 'due_date' => '1405-08-10', 'due_time' => '23:59', 'max_score' => 10, 'allow_late' => 0, 'notify' => 0, 'status' => 'expired', 'done' => 7, 'total' => 9, 'body' => ''],
    ['id' => 4, 'title' => 'نمونه‌سؤال هندسه', 'class_id' => '1', 'type' => 'pdf', 'due_date' => '1405-09-05', 'due_time' => '23:59', 'max_score' => 20, 'allow_late' => 0, 'notify' => 0, 'status' => 'draft', 'done' => 0, 'total' => 22, 'body' => ''],
  ];
  $subs = [['سارا احمدی', '1405-08-26 18:20', 'ok', 18], ['علی رضایی', '1405-08-27 09:05', 'warn', ''], ['نگار کریمی', '1405-08-27 21:40', 'ok', 16], ['رضا مرادی', '—', 'bad', '']];
@endphp

<x-admin.page-head tone="track" icon="file" title="تکالیف" desc="ثبت تکلیف به‌صورت متن یا PDF و بررسی پاسخ دانش‌آموزان">
  <x-admin.btn icon="plus" data-modal-open="hw-modal">تکلیف جدید</x-admin.btn>
</x-admin.page-head>

<div class="adm-stats">
  <x-admin.stat tone="track" icon="file" label="تکالیف فعال" value="8"/>
  <x-admin.stat tone="track" icon="eye" label="منتظر بررسی" value="12" hint="تحویل‌شده بدون نمره"/>
  <x-admin.stat tone="track" icon="check" label="میانگین تحویل" value="۷۲٪"/>
  <x-admin.stat tone="track" icon="clock" label="مهلت امروز" value="2"/>
</div>

<x-admin.panel flush data-filter data-tone="track">
  <x-admin.toolbar placeholder="جستجوی تکلیف…">
    <x-admin.filter-select key="class" placeholder="همه کلاس‌ها" :options="$classes"/>
    <div class="adm-toolbar-end"><x-admin.seg :options="['active' => 'فعال', 'expired' => 'گذشته', 'draft' => 'پیش‌نویس']"/></div>
  </x-admin.toolbar>

  <div class="adm-table-wrap"><table class="adm-table">
    <thead><tr><th>تکلیف</th><th>مهلت</th><th>نوع</th><th>تحویل</th><th>وضعیت</th><th></th></tr></thead>
    <tbody data-filter-list>
      @foreach ($rows as $r)
        <tr data-name="{{ $r['title'] }}" data-state="{{ $r['status'] }}" data-class="{{ $r['class_id'] }}">
          <td><b>{{ $r['title'] }}</b><small>{{ $classes[$r['class_id']] }}</small></td>
          <td class="adm-num is-nowrap">{{ $r['due_date'] }}<small>{{ $r['due_time'] }}</small></td>
          <td>{{ $types[$r['type']] }}</td>
          <td style="min-width:150px"><div class="adm-kv" style="justify-content:space-between"><b class="adm-num">{{ $r['done'] }} از {{ $r['total'] }}</b></div><div class="adm-progress" data-tone="track"><i style="width:{{ round($r['done'] / $r['total'] * 100) }}%"></i></div></td>
          <td><x-admin.badge :tone="$status[$r['status']][0]">{{ $status[$r['status']][1] }}</x-admin.badge></td>
          <td>
            <x-admin.row-actions edit="hw-modal" del="hw-del" :row="$r" :name="$r['title']" :url="url('admin/assignments/'.$r['id'])" edit-title="ویرایش تکلیف">
              <x-admin.btn size="sm" icon="eye" data-modal-open="hw-review" :data-title="'بررسی: '.$r['title']" :data-action="url('admin/assignments/'.$r['id'].'/grades')" data-method="PUT">بررسی</x-admin.btn>
            </x-admin.row-actions>
          </td>
        </tr>
      @endforeach
    </tbody>
  </table></div>
  <x-admin.empty data-empty class="adm-hide" text="تکلیفی پیدا نشد."/>
  <x-slot:footer><x-admin.pager :current="1" :total="38" :per="4"/></x-slot:footer>
</x-admin.panel>
@endsection

@push('modals')
<x-admin.modal id="hw-modal" title="تکلیف جدید" size="lg" :action="url('admin/assignments')">
  <x-admin.legend title="اطلاعات تکلیف"/>
  <x-admin.input label="عنوان تکلیف" name="title" wide required/>
  <x-admin.select label="کلاس" name="class_id" :options="$classes" required/>
  <x-admin.chips label="نوع تکلیف" name="type" :multiple="false" :options="$types" :checked="['both']" :wide="false"/>
  <div data-show-if="type=text|both"><x-admin.textarea label="متن تکلیف" name="body" rows="5"/></div>
  <div data-show-if="type=pdf|both"><x-admin.file label="فایل PDF تکلیف" name="file" accept=".pdf" icon="file" hint="حداکثر ۱۰ مگابایت"/></div>

  <x-admin.legend title="مهلت و نمره"/>
  <x-admin.input label="تاریخ مهلت" name="due_date" placeholder="۱۴۰۵/۰۸/۳۰" required/>
  <x-admin.input label="ساعت مهلت" name="due_time" type="time"/>
  <x-admin.input label="بیشترین نمره" name="max_score" type="number" min="1"/>
  <x-admin.switch label="پذیرش تحویل با تأخیر" name="allow_late"/>
  <x-admin.switch label="اطلاع‌رسانی به دانش‌آموزان و اولیا" name="notify" :checked="true" wide/>
</x-admin.modal>

<x-admin.modal id="hw-review" title="بررسی تکلیف" size="lg" method="PUT" submit="ذخیره نمره‌ها" :action="url('admin/assignments')" plain>
  <div data-scores>
    <div class="adm-table-wrap"><table class="adm-table adm-table-compact">
      <thead><tr><th>دانش‌آموز</th><th>زمان تحویل</th><th>پاسخ</th><th>نمره</th><th>بازخورد</th></tr></thead>
      <tbody>
        @foreach ($subs as $i => [$name, $at, $tone, $score])
          <tr>
            <td><x-admin.person :name="$name"/></td>
            <td class="adm-num">{{ $at }}</td>
            <td>@if ($at !== '—')<x-admin.btn variant="soft" size="sm" icon="down" href="#">دانلود</x-admin.btn>@else<x-admin.badge tone="bad">تحویل نداده</x-admin.badge>@endif</td>
            <td><input class="adm-input adm-score adm-num" type="number" min="0" max="20" name="score[{{ $i }}]" value="{{ $score }}" data-score @disabled($at === '—') aria-label="نمره {{ $name }}"></td>
            <td><input class="adm-input" name="feedback[{{ $i }}]" placeholder="اختیاری" @disabled($at === '—') aria-label="بازخورد"></td>
          </tr>
        @endforeach
      </tbody>
    </table></div>
    <p class="adm-muted" style="margin-top:12px">میانگین نمره‌های واردشده: <b data-avg class="adm-num">—</b></p>
  </div>
</x-admin.modal>

<x-admin.confirm id="hw-del" title="حذف تکلیف" text="تکلیف و پاسخ‌های تحویل‌شده حذف می‌شود."/>
@endpush
