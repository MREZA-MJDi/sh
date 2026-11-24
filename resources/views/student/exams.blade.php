@extends('student.layouts.app')
@section('title', 'آزمون‌ها')
@section('content')
<section class="page" id="exams">
 <x-portal.page-head title="آزمون‌های من" icon="check" tone="tracking"><span class="tag">{{ $exams->total() }} آزمون</span></x-portal.page-head>
 <div class="portal-action-grid">
 @forelse($exams as $exam)
  <article class="portal-exam-card">
   <div class="chead"><h3>{{ $exam->title }}</h3><x-portal.status domain="exam" :value="$exam->status"/></div>
   <small>{{ $exam->course?->title ?? $exam->classroom?->name ?? '—' }}</small>
   @if($exam->provider === 'quiz24' && $exam->integration_status !== 'active')<span class="tag">نسخه داخلی؛ اتصال Quiz24 در انتظار سرویس</span>@endif
   <div class="portal-metrics"><div class="portal-metric"><small>سؤال</small><strong>{{ $exam->questions_count }}</strong></div><div class="portal-metric"><small>زمان شروع</small><strong>{{ $exam->starts_at ? \App\Support\PersianDate::format($exam->starts_at,'Y/m/d H:i') : 'طبق برنامه مدرس' }}</strong></div></div>
   <x-portal.exam-actions :exam="$exam"/>
  </article>
 @empty
  <x-portal.empty-state title="آزمونی برای شما منتشر نشده است." icon="check" tone="tracking"/>
 @endforelse
 </div>{{ $exams->links() }}
</section>
@endsection
