@extends('teacher.layouts.app')
@section('title', 'تکالیف')
@section('content')
<h2>تکالیف و بررسی پاسخ‌ها</h2>
<div class="g">
 @forelse ($homework as $item)
  <section class="c">
   <h3>{{ $item->title }}</h3>
   <p>{{ $item->classroom?->name }} · مهلت: {{ $item->due_at?->format('Y/m/d H:i') ?? 'بدون مهلت' }} · {{ $item->submissions_count }} ارسال</p>
   @forelse ($item->submissions as $submission)
    <div class="row"><div>{{ $submission->student?->name }}<small>{{ $submission->submitted_at?->format('Y/m/d H:i') }} · {{ $submission->status }}</small></div>
     @if ($submission->status === 'submitted')
      <form method="POST" action="{{ route('teacher.homework.review', $submission) }}" class="inline">
       @csrf @method('PATCH')
       <input type="number" name="score" min="0" max="20" step="0.25" placeholder="نمره">
       <input type="text" name="feedback" placeholder="بازخورد" required>
       <button class="b s" type="submit">ثبت بررسی</button>
      </form>
     @else
      <i class="bd gn">{{ $submission->score ?? 'بررسی‌شده' }}</i>
     @endif
    </div>
   @empty
    <p>هنوز پاسخی ارسال نشده است.</p>
   @endforelse
  </section>
 @empty
  <section class="c"><p>تکلیفی برای کلاس‌های تحت مدیریت شما ثبت نشده است.</p></section>
 @endforelse
 {{ $homework->links() }}
</div>
@endsection