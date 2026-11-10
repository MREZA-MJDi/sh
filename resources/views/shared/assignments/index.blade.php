@extends('layouts.app')
@section('title', $role === 'parent' ? 'تکالیف و نتیجه‌ی بررسی' : 'تکالیف')
@section('content')
@if($role === 'parent')<div class="info">تکالیف <b>{{ $child['name'] }}</b>؛ نتیجه‌ی بررسی توسط ادمین برای دانش‌آموز و اولیاء ارسال می‌شود.</div>@endif
<x-card title="فهرست تکالیف">
  <x-slot:actions><div class="tabs" data-tabs data-target="#hw-list">
    <button class="on" data-f="all">همه</button><button data-f="pending">در انتظار ارسال</button><button data-f="review">در حال بررسی</button><button data-f="graded">بررسی‌شده</button><button data-f="late">مهلت گذشته</button>
  </div></x-slot:actions>
  <div class="tw" id="hw-list"><table class="tbl">
    <tr><th>تکلیف</th><th>درس</th><th>مهلت</th><th>وضعیت</th><th>نتیجه‌ی بررسی</th><th></th></tr>
    @foreach($m['assignments'] as $a)
      <tr data-item data-cat="{{ $a['status'] }}">
        <td><b>{{ $a['title'] }}</b></td><td>{{ $a['course'] }}</td><td>{{ $a['due'] }}</td><td><x-status :s="$a['status']"/></td>
        <td>@if($a['score'] !== null){{ fa($a['score']) }} از {{ fa($a['max']) }}@else<span class="mu">—</span>@endif</td>
        <td><a class="btn s {{ in_array($a['status'], ['pending','late']) && $role === 'student' ? '' : 'g' }}" href="{{ $rt('assignments.show', $a['id']) }}">{{ $role === 'student' && in_array($a['status'], ['pending','late']) ? 'ارسال پاسخ' : 'جزئیات' }}</a></td>
      </tr>
    @endforeach
  </table></div>
</x-card>
@endsection
