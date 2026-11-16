@extends('admin.layouts.app')
@section('title', 'پیام‌های اولیا')

@section('content')
@php
  $types = ['criticism' => ['bad', 'انتقاد'], 'suggestion' => ['info', 'پیشنهاد'], 'question' => ['warn', 'پرسش']];
  $teachers = ['' => 'بدون ارجاع', '1' => 'دکتر محمدی', '2' => 'خانم صادقی', '3' => 'آقای نوری'];
  $rows = $messages ?? [
    ['id' => 1, 'parent' => 'مریم حسینی', 'student' => 'علی رضایی', 'type' => 'criticism', 'subject' => 'حجم تکالیف فیزیک', 'body' => 'سلام. حجم تکالیف هفته گذشته برای فرزندم خیلی زیاد بود و فرصت مرور نداشت. ممکن است در این مورد بازنگری شود؟', 'course' => 'فیزیک دهم', 'at' => '۲۰ دقیقه پیش', 'state' => 'new'],
    ['id' => 2, 'parent' => 'حسین احمدی', 'student' => 'سارا احمدی', 'type' => 'suggestion', 'subject' => 'برگزاری کلاس رفع اشکال', 'body' => 'پیشنهاد می‌کنم قبل از امتحانات، یک جلسه رفع اشکال گروهی برگزار شود.', 'course' => 'ریاضی نهم', 'at' => 'امروز ۱۰:۱۵', 'state' => 'new'],
    ['id' => 3, 'parent' => 'زهرا کریمی', 'student' => 'نگار کریمی', 'type' => 'question', 'subject' => 'زمان آزمون میان‌ترم', 'body' => 'تاریخ دقیق آزمون میان‌ترم زیست مشخص شده است؟', 'course' => 'زیست یازدهم', 'at' => 'دیروز', 'state' => 'answered'],
  ];
@endphp

<x-admin.page-head tone="comm" icon="msg" title="پیام‌های اولیا" desc="انتقادات، پیشنهادات و پرسش‌های اولیا؛ پاسخ دهید یا به مدرس مربوطه ارجاع دهید"/>

<div class="adm-stats">
  <x-admin.stat tone="comm" icon="msg" label="پیام‌های جدید" value="5"/>
  <x-admin.stat tone="comm" icon="alert" label="انتقادات باز" value="2"/>
  <x-admin.stat tone="comm" icon="send" label="پاسخ‌داده‌شده این ماه" value="41"/>
  <x-admin.stat tone="comm" icon="clock" label="میانگین زمان پاسخ" value="۶ ساعت"/>
</div>

<x-admin.panel flush data-filter data-tone="comm">
  <x-admin.toolbar placeholder="جستجوی ولی، دانش‌آموز یا موضوع…">
    <x-admin.filter-select key="type" placeholder="همه انواع" :options="collect($types)->map(fn ($t) => $t[1])->all()"/>
    <div class="adm-toolbar-end"><x-admin.seg :options="['new' => 'جدید', 'answered' => 'پاسخ‌داده‌شده']"/></div>
  </x-admin.toolbar>

  <div class="adm-table-wrap"><table class="adm-table">
    <thead><tr><th>فرستنده</th><th>نوع</th><th>موضوع</th><th>دوره</th><th>زمان</th><th></th></tr></thead>
    <tbody data-filter-list>
      @foreach ($rows as $r)
        <tr class="{{ $r['state'] === 'new' ? 'is-unread' : '' }}" data-tone="comm" data-name="{{ $r['parent'].' '.$r['student'].' '.$r['subject'] }}" data-state="{{ $r['state'] }}" data-type="{{ $r['type'] }}">
          <td><x-admin.person :name="$r['parent']" :sub="'ولی '.$r['student']"/></td>
          <td><x-admin.badge :tone="$types[$r['type']][0]">{{ $types[$r['type']][1] }}</x-admin.badge></td>
          <td><span class="adm-msg-title">{{ $r['subject'] }}</span><div class="adm-msg-snippet">{{ $r['body'] }}</div></td>
          <td>{{ $r['course'] }}</td>
          <td class="is-nowrap">{{ $r['at'] }}</td>
          <td>
            <x-admin.row-actions del="msg-del" :name="$r['subject']" :url="url('admin/parent-messages/'.$r['id'])">
              <x-admin.btn size="sm" icon="send" data-modal-open="msg-reply" :data-fill="json_encode(['parent' => $r['parent'], 'subject' => $r['subject'], 'body' => $r['body']], JSON_UNESCAPED_UNICODE)" :data-action="url('admin/parent-messages/'.$r['id'].'/reply')">{{ $r['state'] === 'new' ? 'پاسخ' : 'مشاهده' }}</x-admin.btn>
            </x-admin.row-actions>
          </td>
        </tr>
      @endforeach
    </tbody>
  </table></div>
  <x-admin.empty data-empty class="adm-hide" text="پیامی پیدا نشد."/>
  <x-slot:footer><x-admin.pager :current="1" :total="63" :per="3"/></x-slot:footer>
</x-admin.panel>
@endsection

@push('modals')
<x-admin.modal id="msg-reply" title="پاسخ به پیام ولی" size="lg" submit="ارسال پاسخ" :action="url('admin/parent-messages')">
  <div class="adm-field wide" data-tone="comm">
    <span class="adm-label">پیام <span data-bind="parent"></span></span>
    <div class="adm-quote"><b data-bind="subject"></b><p data-bind="body" style="margin-top:6px"></p></div>
  </div>
  <x-admin.textarea label="پاسخ شما" name="reply" rows="5" required/>
  <x-admin.select label="ارجاع به مدرس" name="forward_to" :options="$teachers" hint="رونوشت پیام برای مدرس ارسال می‌شود"/>
  <x-admin.switch label="علامت‌گذاری به‌عنوان پاسخ‌داده‌شده" name="mark_answered" :checked="true"/>
</x-admin.modal>

<x-admin.confirm id="msg-del" title="حذف پیام" text="پیام از صندوق حذف می‌شود."/>
@endpush
