@extends('public.layouts.academy')
@section('title', 'آزمون‌های رایگان')
@section('content')
<section class="c pe-wrap">
    <header class="pe-intro"><span class="pe-badge">رایگان · بدون نیاز به حساب کاربری</span><h1>آزمون‌های رایگان</h1><p>مهارتت را به چالش بکش و بعد از پایان، پاسخ تشریحی را ببین.</p></header>
    <div class="pe-grid">
        @forelse($exams as $exam)
            <a class="pe-card" href="{{ route('public.exams.show', $exam) }}">
                <span class="pe-badge">{{ $exam->levelLabel() }}</span>
                @if($exam->subject)<span class="pe-badge">{{ $exam->subject }}</span>@endif
                <h2>{{ $exam->title }}</h2><p>{{ $exam->description }}</p>
                <div class="pe-meta">{{ $exam->questions_count }} سؤال <span>·</span> {{ $exam->duration_minutes }} دقیقه</div>
                <span class="pe-button">مشاهده و شروع آزمون</span>
            </a>
        @empty
            <p class="pe-empty">فعلاً آزمون منتشرشده‌ای وجود ندارد.</p>
        @endforelse
    </div>
</section>
@endsection
@push('head')
<style>
.pe-wrap{padding:50px 0 76px}.pe-intro{text-align:center;max-width:720px;margin:0 auto 30px}.pe-intro h1{font-size:clamp(28px,5vw,42px);font-weight:900;margin:14px 0}.pe-intro p,.pe-card p{color:var(--ac-muted);line-height:1.9}
.pe-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,280px),1fr));gap:18px}.pe-card{display:block;padding:26px;border:1px solid var(--ac-line);border-radius:22px;background:var(--ac-surface);box-shadow:0 12px 35px #102b4a0c;color:var(--ac-ink);text-decoration:none;transition:.2s}.pe-card:hover{transform:translateY(-3px);border-color:var(--ac-primary)}
.pe-card h2{font-size:21px;font-weight:900;margin:8px 0}.pe-card p{min-height:54px}.pe-badge{display:inline-flex;padding:5px 12px;border-radius:99px;background:#e9f4ff;color:#1764a0;font-size:12px;font-weight:800;margin:0 4px 8px 0}.pe-meta{color:var(--ac-muted);font-size:13px;margin:16px 0}.pe-button{display:inline-flex;padding:10px 18px;border-radius:12px;background:linear-gradient(120deg,#ffd76a,#ff9d42);color:#432500;font-weight:800}.pe-empty{grid-column:1/-1;text-align:center;color:var(--ac-muted)}
</style>
@endpush