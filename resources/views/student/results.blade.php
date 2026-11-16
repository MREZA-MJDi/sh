@extends('student.layouts.app')
@section('title', 'نمرات و عملکرد')
@section('content')
<section class="page" id="results">
 <div class="card"><div class="chead"><h2>نتایج آزمون‌ها</h2><span class="pill">{{ ($results ?? collect())->count() }} نتیجه</span></div>
  @php $scored = ($results ?? collect())->filter(fn ($r) => $r->score !== null && $r->max_score > 0); @endphp
  <div class="stats"><div class="st s1"><small>میانگین نتایج قابل مشاهده</small><b>{{ $scored->isNotEmpty() ? number_format($scored->avg(fn ($r) => $r->score / $r->max_score * 20), 1) : '—' }}</b><span>از ۲۰</span></div></div>
  @forelse (($results ?? collect()) as $result)
   <div class="trk"><div><b>{{ $result->exam?->title ?? 'آزمون' }}</b><span>{{ $result->exam?->course?->title ?? $result->exam?->classroom?->name }} · {{ $result->created_at?->format('Y/m/d') }}</span></div>
    <span class="tag">{{ $result->score === null ? 'در انتظار نمره' : $result->score.' / '.$result->max_score }}</span>
   </div>
  @empty
   <p>هنوز نتیجه‌ای برای شما ثبت نشده است.</p>
  @endforelse
 </div>
</section>
@endsection