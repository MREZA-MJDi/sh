@extends('admin.layouts.app')

@section('page-title', 'بررسی برگه‌های آزمون')
@section('page-description', 'مشاهده پاسخ دانش‌آموزان و ثبت نمره سؤال‌های تشریحی')

@section('content')
    <div class="panel-page-head"><div><h2>{{ $exam->title }}</h2><p>{{ $attempts->total() }} برگه آزمون</p></div><a class="panel-btn panel-btn-secondary" href="{{ route('admin.exams.index') }}">بازگشت به آزمون‌ها</a></div>
    @forelse ($attempts as $attempt)
        <section class="panel-card mb-4">
            <div class="panel-card-title"><div><h3>{{ $attempt->student?->name ?? 'دانش‌آموز' }}</h3><p>تلاش {{ $attempt->attempt_number }} · {{ $attempt->submitted_at?->format('Y-m-d H:i') }} · {{ $attempt->status === 'pending_review' ? 'در انتظار تصحیح' : 'تصحیح‌شده' }}</p></div><span class="panel-badge">{{ $attempt->score ?? '—' }} / {{ $attempt->max_score }}</span></div>
            <div class="grid gap-3">
                @foreach ($attempt->exam->questions as $question)
                    @php($answer = $attempt->answers->firstWhere('question_id', $question->id))
                    <article class="panel-card">
                        <strong>{{ $loop->iteration }}. {{ $question->question }}</strong>
                        <p class="panel-muted">حداکثر نمره: {{ $question->score }}</p>
                        <div class="panel-details-content">{{ $answer?->answer ?: 'بدون پاسخ' }}</div>
                        @if ($question->type === 'text' && $attempt->status === 'pending_review')
                            <label class="panel-field"><span>نمره پاسخ تشریحی</span><input class="panel-input" form="grade-{{ $attempt->id }}" name="scores[{{ $answer?->id }}]" type="number" step="0.01" min="0" max="{{ $question->score }}" value="{{ $answer?->score ?? 0 }}" required></label>
                        @elseif ($answer)
                            <p class="panel-muted">نمره: {{ $answer->score ?? 'در انتظار تصحیح' }} / {{ $question->score }}</p>
                        @endif
                    </article>
                @endforeach
            </div>
            @if ($attempt->status === 'pending_review')
                <form id="grade-{{ $attempt->id }}" method="POST" action="{{ route('admin.exams.attempts.grade', [$exam, $attempt]) }}" class="mt-3">
                    @csrf @method('PATCH')
                    <div class="panel-field"><label>بازخورد کلی مدرس (اختیاری)</label><textarea class="panel-textarea" name="feedback" rows="3" maxlength="10000"></textarea></div>
                    <button class="panel-btn" type="submit">ثبت نمره و انتشار نتیجه</button>
                </form>
            @elseif (filled($attempt->feedback))
                <div class="panel-alert">{{ $attempt->feedback }}</div>
            @endif
        </section>
    @empty
        <div class="panel-card panel-empty">هنوز دانش‌آموزی این آزمون را ارسال نکرده است.</div>
    @endforelse
    @if ($attempts->hasPages())<div class="panel-pagination">{{ $attempts->links() }}</div>@endif
@endsection
