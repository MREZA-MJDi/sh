@extends('student.layouts.app')
@section('title', 'تکالیف')
@section('content')
<section class="page" id="assignments">
 <div class="card">
  <div class="chead">
   <h2>تکالیف</h2>
   <span class="pill">{{ number_format($homeworks->total()) }} تکلیف</span>
  </div>
  <div class="tw">
   <table class="tbl assignment-table">
    <thead><tr><th>تکلیف</th><th>درس / کلاس</th><th>مهلت</th><th>وضعیت</th><th>پاسخ و پیوست</th></tr></thead>
    <tbody>
    @forelse ($homeworks as $homework)
     @php
      $submission = $homework->submissions->first();
      $reviewed = $submission && ($submission->reviewed_at || in_array($submission->status, ['reviewed', 'graded'], true));
     @endphp
     <tr>
      <td data-label="تکلیف"><span class="assignment-title">{{ $homework->title }}</span></td>
      <td data-label="درس / کلاس">{{ $homework->classroom?->name ?? '—' }}</td>
      <td data-label="مهلت">{{ $homework->due_at ? \App\Support\PersianDate::format($homework->due_at, 'Y/m/d H:i') : 'بدون مهلت' }}</td>
      <td data-label="وضعیت">
       @if($reviewed)
        <span class="tag d">بررسی‌شده</span>
       @elseif($submission)
        <span class="tag">ارسال‌شده · در انتظار بررسی</span>
       @else
        <span class="tag w">در انتظار ارسال</span>
       @endif
       @if($submission?->score !== null)
        <span class="assignment-meta">نمره: {{ $submission->score }} از ۲۰</span>
       @endif
      </td>
      <td class="assignment-action" data-label="پاسخ و پیوست">
       @if($submission?->files->isNotEmpty())
        <div class="assignment-files">
         @foreach($submission->files as $file)
          <a class="pill" href="{{ route('student.homework.submission-files.download', $file) }}">{{ $file->original_name }}</a>
          @if(!$reviewed)
           <form method="POST" action="{{ route('student.homework.submission-files.destroy', $file) }}">@csrf @method('DELETE')<button class="pill" type="submit" data-confirm="این فایل از پاسخ تکلیف حذف شود؟">حذف فایل</button></form>
          @endif
         @endforeach
        </div>
       @endif
       @if($submission?->feedback)
        <div class="assignment-feedback"><b>بازخورد مدرس:</b> {{ $submission->feedback }}</div>
       @endif
       @if(!$reviewed)
        <details>
         <summary class="btn s">{{ $submission ? 'ویرایش پاسخ' : 'ارسال پاسخ' }}</summary>
         <form class="assignment-form" method="POST" action="{{ route('student.homework.submissions.store', $homework) }}" enctype="multipart/form-data">
          @csrf
          <label for="answer-{{ $homework->id }}">متن پاسخ</label>
          <textarea class="fld" id="answer-{{ $homework->id }}" name="answer" rows="3" maxlength="12000" placeholder="متن پاسخ را بنویسید...">{{ old('answer', $submission?->answer) }}</textarea>
          <label for="files-{{ $homework->id }}">افزودن فایل (حداکثر ۵ فایل، هر فایل ۱۰ مگابایت)</label>
          <input id="files-{{ $homework->id }}" type="file" name="files[]" multiple accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.jpg,.jpeg,.png,.zip,.txt">
          <button class="btn s" type="submit">ثبت پاسخ</button>
         </form>
        </details>
       @else
        <span class="assignment-meta">پس از بررسی مدرس، پاسخ قابل ویرایش نیست.</span>
       @endif
      </td>
     </tr>
    @empty
     <tr><td colspan="5">تکلیفی برای کلاس‌های فعال شما ثبت نشده است.</td></tr>
    @endforelse
    </tbody>
   </table>
  </div>
  <div class="chead">{{ $homeworks->links() }}</div>
 </div>
</section>
@endsection
