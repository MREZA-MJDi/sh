@extends('public.layouts.academy')
@section('title', $exam->title)
@section('content')
<section class="c pe-wrap">
    <div class="pe-panel">
        <span class="pe-badge">{{ $exam->levelLabel() }}</span>
        @if($exam->subject)<span class="pe-badge">{{ $exam->subject }}</span>@endif
        <h1>{{ $exam->title }}</h1><p class="pe-muted">{{ $exam->description }}</p>
        <ul class="pe-muted">
            <li>{{ $exam->questions_count }} سؤال چهارگزینه‌ای · {{ $exam->duration_minutes }} دقیقه</li>
            <li>حداقل نمره قبولی: {{ $exam->pass_percent }}٪</li>
            @if($exam->negative_marking)<li>نمره منفی دارد؛ پاسخ نزده نمره منفی ندارد.</li>@endif
            <li>هر شماره موبایل فقط یک بار می‌تواند در این آزمون شرکت کند.</li>
        </ul>
        <form method="POST" action="{{ route('public.exams.start', $exam) }}">@csrf
            <label class="pe-field">نام و نام خانوادگی<input name="name" value="{{ old('name') }}" autocomplete="name" required maxlength="60"></label>
            @error('name')<p class="pe-error">{{ $message }}</p>@enderror
            <label class="pe-field">شماره موبایل<input name="phone" type="tel" inputmode="numeric" autocomplete="tel" placeholder="09123456789" value="{{ old('phone') }}" required></label>
            @error('phone')<p class="pe-error">{{ $message }}</p>@enderror
            <button class="pe-button" type="submit">شروع آزمون</button>
        </form>
    </div>
</section>
@endsection
@push('head')
<style>
.pe-wrap{max-width:720px;padding:48px 16px 72px}.pe-panel{padding:clamp(22px,5vw,38px);border:1px solid var(--ac-line);border-radius:24px;background:var(--ac-surface);box-shadow:0 18px 55px #102b4a10}.pe-panel h1{font-size:clamp(24px,4vw,34px);font-weight:900;margin:10px 0}.pe-muted{color:var(--ac-muted);line-height:2}.pe-panel ul{padding-right:22px;line-height:2.2;margin:18px 0}
.pe-badge{display:inline-flex;padding:5px 12px;border-radius:99px;background:#e9f4ff;color:#1764a0;font-size:12px;font-weight:800;margin:0 4px 8px 0}.pe-field{display:block;font-weight:700;margin:16px 0}.pe-field input{display:block;width:100%;margin-top:7px;padding:13px 14px;border:1px solid var(--ac-line);border-radius:12px;background:var(--ac-bg,#fff);color:var(--ac-ink);font:inherit}.pe-button{border:0;display:inline-flex;justify-content:center;padding:12px 24px;border-radius:12px;background:linear-gradient(120deg,#ffd76a,#ff9d42);color:#432500;font:inherit;font-weight:900;cursor:pointer;text-decoration:none}.pe-error{color:#dc2626;font-size:13px}
</style>
@endpush