@extends('student.layouts.app')
@section('title', 'تکالیف')
@section('content')
<section class="page" id="assignments">
 <div class="card"><div class="chead"><h2>تکالیف</h2><span class="pill">{{ ($homeworks ?? collect())->count() }} تکلیف</span></div>
  @forelse (($homeworks ?? collect()) as $homework)
   @php $submission = $homework->submissions->first(); @endphp
   <div class="trk">
    <div><b>{{ $homework->title }}</b><span>{{ $homework->classroom?->name }} · مهلت: {{ $homework->due_at?->format('Y/m/d H:i') ?? 'بدون مهلت' }}</span>
     @if ($submission)<span>وضعیت: {{ $submission->status === 'submitted' ? 'ارسال‌شده' : $submission->status }}</span>@endif
    </div>
    @if (!$submission || !$submission->reviewed_at)
     <form method="POST" action="{{ route('student.homework.submissions.store', $homework) }}" enctype="multipart/form-data">
      @csrf
      <textarea name="answer" rows="2" placeholder="متن پاسخ (اختیاری)"></textarea>
      <input type="file" name="files[]" multiple>
      <button class="btn s" type="submit">ثبت پاسخ</button>
     </form>
    @else
     <span class="tag">بررسی‌شده · {{ $submission->score ?? 'بدون نمره' }}</span>
    @endif
   </div>
  @empty
   <p>تکلیفی برای کلاس‌های فعال شما ثبت نشده است.</p>
  @endforelse
 </div>
</section>
@endsection