@extends('admin.layouts.app')
@section('title', 'پرداخت‌ها')

@section('content')
@php
  $students = ['1' => 'سارا احمدی', '2' => 'علی رضایی', '3' => 'نگار کریمی', '4' => 'رضا مرادی'];
  $itemTypes = ['plan' => 'پلن دوره', 'resource' => 'منبع / محصول'];
  $methods = ['gateway' => 'درگاه آنلاین', 'card' => 'کارت‌به‌کارت', 'cash' => 'نقدی / حضوری'];
  $status = ['paid' => ['ok', 'پرداخت‌شده'], 'pending' => ['warn', 'در انتظار تأیید'], 'failed' => ['bad', 'ناموفق'], 'refunded' => ['mute', 'مسترد']];
  $rows = $payments ?? [
    ['id' => 1, 'code' => 'TX-90412', 'student_id' => '1', 'item_type' => 'plan', 'item' => 'ریاضی نهم — پلن پیشرفته', 'amount' => 1800000, 'method' => 'gateway', 'date' => '1405-08-25', 'status' => 'paid', 'ref' => '4471830021', 'note' => ''],
    ['id' => 2, 'code' => 'TX-90411', 'student_id' => '2', 'item_type' => 'plan', 'item' => 'فیزیک دهم — پلن پایه', 'amount' => 1890000, 'method' => 'card', 'date' => '1405-08-25', 'status' => 'pending', 'ref' => '', 'note' => 'رسید کارت‌به‌کارت بارگذاری شده'],
    ['id' => 3, 'code' => 'TX-90408', 'student_id' => '3', 'item_type' => 'plan', 'item' => 'زیست یازدهم — فقط کلاس زنده', 'amount' => 150000, 'method' => 'gateway', 'date' => '1405-08-24', 'status' => 'failed', 'ref' => '', 'note' => ''],
    ['id' => 4, 'code' => 'TX-90397', 'student_id' => '4', 'item_type' => 'resource', 'item' => 'جزوه هندسه نهم', 'amount' => 120000, 'method' => 'cash', 'date' => '1405-08-22', 'status' => 'refunded', 'ref' => '', 'note' => 'انصراف دانش‌آموز'],
  ];
@endphp

<x-admin.page-head tone="shop" icon="card" title="پرداخت‌ها" desc="پرداخت پلن‌های دوره و محصولات؛ تأیید پرداخت، ثبت‌نام و مجوز دسترسی می‌سازد">
  <x-admin.btn icon="plus" data-modal-open="pay-modal">ثبت پرداخت دستی</x-admin.btn>
</x-admin.page-head>

<div class="adm-stats">
  <x-admin.stat tone="shop" icon="card" label="درآمد امروز" value="3,690,000" hint="تومان"/>
  <x-admin.stat tone="shop" icon="chart" label="درآمد این ماه" value="148,200,000" hint="تومان"/>
  <x-admin.stat tone="shop" icon="clock" label="در انتظار تأیید" value="7"/>
  <x-admin.stat tone="shop" icon="refresh" label="مسترد‌شده" value="2"/>
</div>

<x-admin.panel flush data-filter data-tone="shop">
  <x-admin.toolbar placeholder="جستجوی کد تراکنش، دانش‌آموز یا مورد…">
    <x-admin.filter-select key="method" placeholder="همه روش‌ها" :options="$methods"/>
    <div class="adm-toolbar-end"><x-admin.seg :options="['paid' => 'پرداخت‌شده', 'pending' => 'در انتظار', 'failed' => 'ناموفق', 'refunded' => 'مسترد']"/></div>
  </x-admin.toolbar>

  <div class="adm-table-wrap"><table class="adm-table">
    <thead><tr><th>تراکنش</th><th>دانش‌آموز</th><th>مورد</th><th>مبلغ (تومان)</th><th>روش</th><th>وضعیت</th><th></th></tr></thead>
    <tbody data-filter-list>
      @foreach ($rows as $r)
        <tr data-name="{{ $r['code'].' '.$students[$r['student_id']].' '.$r['item'] }}" data-state="{{ $r['status'] }}" data-method="{{ $r['method'] }}">
          <td class="adm-num is-nowrap"><b dir="ltr">{{ $r['code'] }}</b><small>{{ $r['date'] }}</small></td>
          <td><x-admin.person :name="$students[$r['student_id']]"/></td>
          <td>{{ $r['item'] }}<small>{{ $itemTypes[$r['item_type']] }}</small></td>
          <td class="adm-num"><b>{{ number_format($r['amount']) }}</b></td>
          <td>{{ $methods[$r['method']] }}</td>
          <td><x-admin.badge :tone="$status[$r['status']][0]">{{ $status[$r['status']][1] }}</x-admin.badge></td>
          <td>
            <x-admin.row-actions edit="pay-modal" del="pay-del" :row="$r" :name="$r['code']" :url="url('admin/payments/'.$r['id'])" edit-title="ویرایش پرداخت">
              @if ($r['status'] === 'pending')<x-admin.btn variant="ok" size="sm" icon="check" data-modal-open="pay-approve" :data-name="$r['code'].' — '.number_format($r['amount']).' تومان'" :data-action="url('admin/payments/'.$r['id'].'/approve')" data-method="PATCH">تأیید</x-admin.btn>@endif
              @if ($r['status'] === 'paid')<x-admin.btn variant="line" size="sm" icon="refresh" data-modal-open="pay-refund" :data-name="$r['code'].' — '.number_format($r['amount']).' تومان'" :data-action="url('admin/payments/'.$r['id'].'/refund')" data-method="PATCH">استرداد</x-admin.btn>@endif
            </x-admin.row-actions>
          </td>
        </tr>
      @endforeach
    </tbody>
  </table></div>
  <x-admin.empty data-empty class="adm-hide" text="پرداختی پیدا نشد."/>
  <x-slot:footer><x-admin.pager :current="1" :total="412" :per="4"/></x-slot:footer>
</x-admin.panel>
@endsection

@push('modals')
<x-admin.modal id="pay-modal" title="ثبت پرداخت دستی" size="lg" :action="url('admin/payments')">
  <x-admin.legend title="پرداخت‌کننده و مورد"/>
  <x-admin.select label="دانش‌آموز" name="student_id" :options="$students" required/>
  <x-admin.select label="نوع مورد" name="item_type" :options="$itemTypes"/>
  <x-admin.input label="عنوان مورد" name="item" wide required hint="مثلاً «ریاضی دهم — پلن پیشرفته»"/>
  <x-admin.legend title="جزئیات پرداخت"/>
  <x-admin.input label="مبلغ" name="amount" type="number" min="0" step="1000" addon="تومان" required/>
  <x-admin.select label="روش پرداخت" name="method" :options="$methods"/>
  <x-admin.input label="تاریخ پرداخت" name="date" placeholder="۱۴۰۵/۰۸/۲۵"/>
  <div data-show-if="method=card|gateway"><x-admin.input label="شماره پیگیری" name="ref"/></div>
  <x-admin.select label="وضعیت" name="status" :options="collect($status)->map(fn ($s) => $s[1])->all()"/>
  <div data-show-if="method=card"><x-admin.file label="تصویر رسید" name="receipt" accept="image/*,.pdf" icon="file" hint="تصویر یا PDF"/></div>
  <x-admin.textarea label="یادداشت" name="note" rows="2"/>
  <x-admin.switch label="ثبت‌نام و مجوز بلافاصله ساخته شود" name="grant_access" :checked="true" hint="فقط وقتی وضعیت «پرداخت‌شده» باشد" wide/>
</x-admin.modal>

<x-admin.modal id="pay-approve" title="تأیید پرداخت" size="sm" method="PATCH" submit="تأیید و ساخت ثبت‌نام" :action="url('admin/payments')" plain>
  <p class="adm-confirm"><x-admin.icon name="check" style="color:var(--ok)"/><span><b data-modal-name></b><br>پرداخت تأیید می‌شود، ثبت‌نام ساخته می‌شود و مجوزهای پلن فعال می‌گردد.</span></p>
</x-admin.modal>

<x-admin.modal id="pay-refund" title="استرداد پرداخت" size="sm" method="PATCH" submit="استرداد وجه" danger :action="url('admin/payments')" plain>
  <p class="adm-confirm"><x-admin.icon name="alert"/><span><b data-modal-name></b><br>وجه مسترد و دسترسی دانش‌آموز غیرفعال می‌شود.</span></p>
</x-admin.modal>

<x-admin.confirm id="pay-del" title="حذف تراکنش" text="تراکنش از گزارش‌های مالی حذف می‌شود."/>
@endpush
