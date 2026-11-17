@extends('student.layouts.app')
@section('title', 'آزمون‌ها')
@section('content')
<section class="page" id="exams"><div class="card"><div class="chead"><h2>آزمون‌های من</h2><span class="pill">{{ $exams->total() }} آزمون</span></div>
 <div class="tw"><table class="tbl"><tr><th>آزمون</th><th>درس / کلاس</th><th>زمان</th><th>وضعیت</th><th></th></tr>
 @forelse ($exams as $exam)
  <tr><td>{{ $exam->title }}@if($exam->provider === 'quiz24' && $exam->integration_status !== 'active')<small class="tag">نسخه داخلی؛ اتصال Quiz24 در انتظار سرویس</small>@endif</td><td>{{ $exam->course?->title ?? $exam->classroom?->name ?? '—' }}</td><td>{{ $exam->starts_at?->format('Y/m/d H:i') ?? 'طبق برنامه مدرس' }}</td><td><span class="tag">{{ $exam->status }}</span></td><td>
   <a class="btn s" href="{{ route('student.exams.show', $exam) }}">جزئیات</a>
   @if ($exam->status === 'published')
    <form method="POST" action="{{ route('student.exams.start', $exam) }}" class="inline">@csrf<button class="btn s" type="submit">شروع آزمون</button></form>
   @endif
  </td></tr>
 @empty
  <tr><td colspan="5">آزمونی برای شما منتشر نشده است.</td></tr>
 @endforelse
 </table></div>{{ $exams->links() }}</div></section>
@endsection