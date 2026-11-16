@extends('student.layouts.app')
@section('title', 'تکالیف')
@section('content')
<section class="page" id="assignments"><div class="card"><div class="chead"><h2>تکالیف</h2><span class="pill">{{ ($homeworks ?? collect())->count() }} تکلیف</span></div>
 <div class="tw"><table class="tbl"><tr><th>تکلیف</th><th>درس</th><th>مهلت</th><th>وضعیت</th><th></th></tr>
 @forelse (($homeworks ?? collect()) as $homework)
  @php $submission = $homework->submissions->first(); @endphp
  <tr>
   <td>{{ $homework->title }}</td>
   <td>{{ $homework->classroom?->name ?? '—' }}</td>
   <td>{{ $homework->due_at?->format('Y/m/d H:i') ?? 'بدون مهلت' }}</td>
   <td><span class="tag {{ $submission ? 'd' : 'w' }}">{{ $submission ? ($submission->reviewed_at ? 'بررسی‌شده' : 'ارسال‌شده') : 'در انتظار' }}</span></td>
   <td>
    @if (!$submission || !$submission->reviewed_at)
     <details><summary class="btn s">ارسال پاسخ</summary>
      <form method="POST" action="{{ route('student.homework.submissions.store', $homework) }}" enctype="multipart/form-data">
       @csrf<textarea name="answer" rows="2" placeholder="متن پاسخ (اختیاری)"></textarea><input type="file" name="files[]" multiple>
       <button class="btn s" type="submit">ثبت پاسخ</button>
      </form>
     </details>
    @elseif ($submission->score !== null)
     <span class="pill">نمره: {{ $submission->score }}</span>
    @else
     <span class="pill">مشاهده بازخورد</span>
    @endif
   </td>
  </tr>
 @empty
  <tr><td colspan="5">تکلیفی برای کلاس‌های فعال شما ثبت نشده است.</td></tr>
 @endforelse
 </table></div>
</div></section>
@endsection
