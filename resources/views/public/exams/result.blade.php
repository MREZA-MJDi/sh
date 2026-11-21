@extends('public.layouts.academy')
@section('title', 'نتیجه آزمون · '.$attempt->snapshot['title'])
@section('content')
<section class="c pe-wrap">
    <div class="pe-panel pe-result"><div class="pe-score">{{ number_format($attempt->score_percent, 0) }}٪</div>
        <h1>{{ $passed ? 'آفرین، آزمون را با موفقیت گذراندی!' : 'این بار نشد؛ پاسخ‌ها را مرور کن.' }}</h1>
        <div class="pe-stats"><span>✓ درست: {{ $attempt->correct_count }}</span><span>✗ غلط: {{ $attempt->wrong_count }}</span><span>○ نزده: {{ $attempt->blank_count }}</span></div>
        <p class="pe-muted">حداقل نمره قبولی این آزمون {{ data_get($attempt->snapshot, 'pass_percent', 50) }}٪ است.</p>
    </div>
    <h2 style="margin:28px 0 14px">مرور پاسخ‌ها و توضیحات</h2>
    @foreach($items as $index => $question)
        @php($picked = $attempt->answers[$question['id']] ?? null)
        <article class="pe-panel" style="margin:12px 0"><p class="pe-muted">سؤال {{ $index + 1 }} {{ $picked ? '' : '· بی‌پاسخ' }}</p><h3>{{ $question['body'] }}</h3>
            @foreach($question['options'] as $option)
                <div class="pe-option {{ $option['is_correct'] ? 'pe-correct' : ((int)$picked === (int)$option['id'] ? 'pe-wrong' : '') }}">{{ $option['is_correct'] ? '✓' : ((int)$picked === (int)$option['id'] ? '✗' : '•') }} {{ $option['body'] }}</div>
            @endforeach
            @if($question['explanation'])<p class="pe-muted">💡 {{ $question['explanation'] }}</p>@endif
        </article>
    @endforeach
    <a class="pe-button" href="{{ route('public.exams.index') }}">بازگشت به آزمون‌های رایگان</a>
</section>
@endsection
@push('head')
<style>
.pe-wrap{max-width:840px;padding:40px 16px 72px}.pe-panel{padding:24px;border:1px solid var(--ac-line);border-radius:22px;background:var(--ac-surface);margin:12px 0}.pe-result{text-align:center}.pe-score{width:150px;height:150px;border-radius:50%;display:grid;place-items:center;margin:0 auto 18px;background:linear-gradient(135deg,#7c3aed,#06b6d4);color:#fff;font-size:36px;font-weight:900;box-shadow:inset 0 0 0 10px #ffffff25}.pe-result h1{font-size:24px;font-weight:900}.pe-muted{color:var(--ac-muted);line-height:2}.pe-stats{display:flex;gap:9px;justify-content:center;flex-wrap:wrap;margin:14px}.pe-stats span{padding:7px 12px;border:1px solid var(--ac-line);border-radius:99px}.pe-option{padding:12px;border:1px solid var(--ac-line);border-radius:12px;margin:8px 0}.pe-correct{border-color:#16a34a;background:#16a34a12}.pe-wrong{border-color:#dc2626;background:#dc262612}.pe-button{display:inline-flex;padding:11px 18px;border-radius:12px;background:linear-gradient(120deg,#ffd76a,#ff9d42);color:#432500;text-decoration:none;font-weight:900}
</style>
@endpush