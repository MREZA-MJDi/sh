@props(['exam','details'=>false])
@php
 $attempts = $exam->attempts;
 $active = $attempts->firstWhere('status','in_progress');
 $used = $attempts->whereIn('status',['graded','pending_review','submitted','expired'])->count();
 if ($active && (int)$exam->duration_minutes > 0 && $active->started_at && now()->gt($active->started_at->copy()->addMinutes((int)$exam->duration_minutes)->addSeconds(15))) { $active = null; $used++; }
 $limit = max(1,min(5,(int)$exam->attempts_allowed));
 $ready = $exam->status === 'published' && (!$exam->starts_at || !$exam->starts_at->isFuture()) && ($exam->questions_count ?? $exam->questions->count()) > 0;
@endphp
<div class="portal-exam-actions">
 @if(!$details)<a class="pill" href="{{ route('student.exams.show',$exam) }}">جزئیات</a>@endif
 @if($ready && ($active || $used < $limit) && auth()->user()->hasPermissionTo('exams.attempt'))
  <form method="POST" action="{{ route('student.exams.start',$exam) }}">@csrf<button class="btn s" type="submit"><x-portal.icon name="check"/>{{ $active ? 'ادامه آزمون' : 'شروع آزمون' }}</button></form>
 @elseif($exam->starts_at?->isFuture())<small>هنوز وقت شروع نرسیده است.</small>
 @elseif(!$ready)<small>این آزمون هنوز برای پاسخ‌دادن آماده نیست.</small>
 @elseif($used >= $limit)<small>همهٔ دفعات مجاز شرکت در این آزمون را استفاده کرده‌ای.</small>@endif
 @if($attempts->isNotEmpty())<a class="pill" href="{{ route('student.exams.result',$exam) }}">دیدن نتیجه</a>@endif
</div>
