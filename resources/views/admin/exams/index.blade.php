@extends('admin.layouts.app')
@section('title', 'آزمون‌ها')

@section('content')
@php
  $classes = ['1' => 'ریاضی نهم — الف', '2' => 'فیزیک دهم — ب', '3' => 'زیست یازدهم — ج'];
  $kinds = ['class' => 'آزمون کلاسی', 'online' => 'آزمون آنلاین', 'midterm' => 'امتحان میان‌ترم'];
  $status = ['planned' => ['info', 'برنامه‌ریزی‌شده'], 'graded' => ['ok', 'نمره‌دهی شده'], 'pending' => ['warn', 'منتظر نمره‌دهی']];
  $rows = $exams ?? [
    ['id' => 1, 'title' => 'آزمون فصل ۳ ریاضی', 'class_id' => '1', 'kind' => 'class', 'date' => '1405-08-29', 'time' => '16:00', 'duration' => 60, 'max_score' => 20, 'status' => 'planned', 'avg' => '', 'publish' => 0],
    ['id' => 2, 'title' => 'آزمون آنلاین فیزیک', 'class_id' => '2', 'kind' => 'online', 'date' => '1405-08-22', 'time' => '17:30', 'duration' => 45, 'max_score' => 20, 'status' => 'pending', 'avg' => '', 'publish' => 0],
    ['id' => 3, 'title' => 'میان‌ترم زیست', 'class_id' => '3', 'kind' => 'midterm', 'date' => '1405-08-15', 'time' => '15:00', 'duration' => 90, 'max_score' => 20, 'status' => 'graded', 'avg' => 14.6, 'publish' => 1],
  ];
  $results = [['سارا احمدی', 18], ['علی رضایی', 14.5], ['نگار کریمی', 16], ['رضا مرادی', '']];
@endphp

<x-admin.page-head tone="track" icon="exam" title="آزمون‌ها" desc="برنامه‌ریزی آزمون، ثبت نمره و انتشار نتایج برای دانش‌آموز و ولی">
  <x-admin.btn icon="plus" data-modal-open="exam-modal">آزمون جدید</x-admin.btn>
</x-admin.page-head>

<div class="adm-stats">
  <x-admin.stat tone="track" icon="exam" label="آزمون‌های پیش‌رو" value="4"/>
  <x-admin.stat tone="track" icon="edit" label="منتظر نمره‌دهی" value="2"/>
  <x-admin.stat tone="track" icon="chart" label="میانگین کل" value="14.8"/>
  <x-admin.stat tone="track" icon="eye" label="نتایج منتشرشده" value="19"/>
</div>

<x-admin.panel flush data-filter data-tone="track">
  <x-admin.toolbar placeholder="جستجوی آزمون…">
    <x-admin.filter-select key="class" placeholder="همه کلاس‌ها" :options="$classes"/>
    <div class="adm-toolbar-end"><x-admin.seg :options="['planned' => 'پیش‌رو', 'pending' => 'منتظر نمره', 'graded' => 'نمره‌دهی شده']"/></div>
  </x-admin.toolbar>

  <div class="adm-table-wrap"><table class="adm-table">
    <thead><tr><th>آزمون</th><th>زمان</th><th>مدت</th><th>نمره کل</th><th>میانگین</th><th>وضعیت</th><th></th></tr></thead>
    <tbody data-filter-list>
      @foreach ($rows as $r)
        <tr data-name="{{ $r['title'] }}" data-state="{{ $r['status'] }}" data-class="{{ $r['class_id'] }}">
          <td><b>{{ $r['title'] }}</b><small>{{ $classes[$r['class_id']] }} · {{ $kinds[$r['kind']] }}</small></td>
          <td class="adm-num is-nowrap">{{ $r['date'] }}<small>{{ $r['time'] }}</small></td>
          <td class="adm-num">{{ $r['duration'] }} دقیقه</td>
          <td class="adm-num">{{ $r['max_score'] }}</td>
          <td class="adm-num">{{ $r['avg'] !== '' ? $r['avg'] : '—' }}</td>
          <td><x-admin.badge :tone="$status[$r['status']][0]">{{ $status[$r['status']][1] }}</x-admin.badge></td>
          <td>
            <x-admin.row-actions edit="exam-modal" del="exam-del" :row="$r" :name="$r['title']" :url="url('admin/exams/'.$r['id'])" edit-title="ویرایش آزمون">
              <x-admin.btn size="sm" icon="chart" data-modal-open="exam-results" :data-title="'نتایج: '.$r['title']" :data-action="url('admin/exams/'.$r['id'].'/results')" data-method="PUT">نتایج</x-admin.btn>
            </x-admin.row-actions>
          </td>
        </tr>
      @endforeach
    </tbody>
  </table></div>
  <x-admin.empty data-empty class="adm-hide" text="آزمونی پیدا نشد."/>
  <x-slot:footer><x-admin.pager :current="1" :total="29" :per="3"/></x-slot:footer>
</x-admin.panel>
@endsection

@push('modals')
<x-admin.modal id="exam-modal" title="آزمون جدید" size="lg" :action="url('admin/exams')">
  <x-admin.legend title="اطلاعات آزمون"/>
  <x-admin.input label="عنوان آزمون" name="title" wide required/>
  <x-admin.select label="کلاس" name="class_id" :options="$classes" required/>
  <x-admin.select label="نوع آزمون" name="kind" :options="$kinds"/>
  <x-admin.legend title="زمان و نمره"/>
  <x-admin.input label="تاریخ" name="date" placeholder="۱۴۰۵/۰۸/۲۹" required/>
  <x-admin.input label="ساعت" name="time" type="time"/>
  <x-admin.input label="مدت" name="duration" type="number" min="5" addon="دقیقه"/>
  <x-admin.input label="نمره کل" name="max_score" type="number" min="1"/>
  <x-admin.file label="فایل سؤالات (اختیاری)" name="file" accept=".pdf" icon="file" hint="PDF"/>
  <x-admin.switch label="نتایج برای دانش‌آموز و ولی نمایش داده شود" name="publish" hint="پس از ثبت نمره‌ها" wide/>
</x-admin.modal>

<x-admin.modal id="exam-results" title="نتایج آزمون" size="lg" method="PUT" submit="ذخیره نتایج" :action="url('admin/exams')" plain>
  <div data-scores>
    <div class="adm-table-wrap"><table class="adm-table adm-table-compact">
      <thead><tr><th>دانش‌آموز</th><th>نمره از ۲۰</th><th>توضیح</th></tr></thead>
      <tbody>
        @foreach ($results as $i => [$name, $score])
          <tr><td><x-admin.person :name="$name"/></td>
          <td><input class="adm-input adm-score adm-num" type="number" min="0" max="20" step="0.25" name="score[{{ $i }}]" value="{{ $score }}" data-score aria-label="نمره {{ $name }}"></td>
          <td><input class="adm-input" name="note[{{ $i }}]" placeholder="اختیاری" aria-label="توضیح"></td></tr>
        @endforeach
      </tbody>
    </table></div>
    <p class="adm-muted" style="margin-top:12px">میانگین کلاس: <b data-avg class="adm-num">—</b></p>
  </div>
</x-admin.modal>

<x-admin.confirm id="exam-del" title="حذف آزمون" text="آزمون و نمره‌های ثبت‌شده حذف می‌شود."/>
@endpush
