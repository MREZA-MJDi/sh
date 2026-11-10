@extends('admin.layouts.app')

@section('page-title', 'سؤال‌های آزمون')
@section('page-description', 'طراحی سؤال‌های تستی و تشریحی و تعیین نمره هر سؤال')

@section('content')
    <div class="panel-page-head">
        <div><h2>{{ $exam->title }}</h2><p>مجموع نمره سؤال‌ها: {{ number_format((float) $exam->questions->sum('score'), 2) }}</p></div>
        <div class="flex flex-wrap gap-2">
            <a class="panel-btn panel-btn-secondary" href="{{ route('admin.exams.index') }}">بازگشت</a>
            <a class="panel-btn panel-btn-secondary" href="{{ route('admin.exams.attempts', $exam) }}">برگه‌های ارسال‌شده</a>
        </div>
    </div>

    <section class="panel-card mb-4">
        <div class="panel-card-title"><div><h3>افزودن سؤال</h3><p>برای سؤال تستی، گزینه‌ها را هرکدام در یک خط وارد کنید.</p></div></div>
        <form method="POST" action="{{ route('admin.exams.questions.store', $exam) }}" class="panel-form-grid">
            @csrf
            <div class="panel-field"><label>نوع سؤال</label><select class="panel-select" name="type"><option value="single">تستی تک‌پاسخی</option><option value="multiple">تستی چندپاسخی</option><option value="text">تشریحی</option></select></div>
            <div class="panel-field"><label>نمره</label><input class="panel-input" name="score" type="number" step="0.01" min="0.01" max="10000" value="1" required></div>
            <div class="panel-field"><label>ترتیب</label><input class="panel-input" name="sort_order" type="number" min="0" value="{{ $exam->questions->count() + 1 }}"></div>
            <div class="panel-field panel-field-wide"><label>متن سؤال</label><textarea class="panel-textarea" name="question" rows="3" required maxlength="10000"></textarea></div>
            <div class="panel-field panel-field-wide"><label>گزینه‌ها (هر خط یک گزینه؛ فقط برای سؤال تستی)</label><textarea class="panel-textarea" name="options_text" rows="4" placeholder="گزینه اول&#10;گزینه دوم&#10;گزینه سوم"></textarea></div>
            <div class="panel-field panel-field-wide"><label>پاسخ صحیح</label><input class="panel-input" name="correct_answer" maxlength="2000" placeholder="برای چندپاسخی، گزینه‌ها را با ویرگول جدا کن"></div>
            <div><button class="panel-btn" type="submit">افزودن سؤال</button></div>
        </form>
    </section>

    @forelse ($exam->questions as $question)
        <section class="panel-card mb-3">
            <div class="panel-card-title">
                <div><h3>سؤال {{ $loop->iteration }} · {{ $question->type === 'text' ? 'تشریحی' : ($question->type === 'multiple' ? 'چندپاسخی' : 'تک‌پاسخی') }}</h3><p>{{ number_format((float) $question->score, 2) }} نمره</p></div>
                <span class="panel-badge">#{{ $question->id }}</span>
            </div>
            <form method="POST" action="{{ route('admin.exams.questions.update', [$exam, $question]) }}" class="panel-form-grid">
                @csrf @method('PATCH')
                <div class="panel-field"><label>نوع</label><select class="panel-select" name="type"><option value="single" @selected($question->type === 'single')>تک‌پاسخی</option><option value="multiple" @selected($question->type === 'multiple')>چندپاسخی</option><option value="text" @selected($question->type === 'text')>تشریحی</option></select></div>
                <div class="panel-field"><label>نمره</label><input class="panel-input" name="score" type="number" step="0.01" min="0.01" max="10000" value="{{ $question->score }}" required></div>
                <div class="panel-field"><label>ترتیب</label><input class="panel-input" name="sort_order" type="number" min="0" value="{{ $question->sort_order }}"></div>
                <div class="panel-field panel-field-wide"><label>متن سؤال</label><textarea class="panel-textarea" name="question" rows="3" required>{{ $question->question }}</textarea></div>
                <div class="panel-field panel-field-wide"><label>گزینه‌ها (هر خط یک گزینه)</label><textarea class="panel-textarea" name="options_text" rows="4">{{ implode("\n", $question->options ?? []) }}</textarea></div>
                <div class="panel-field panel-field-wide"><label>پاسخ صحیح</label><input class="panel-input" name="correct_answer" value="{{ $question->type === 'multiple' ? implode(',', json_decode($question->correct_answer ?: '[]', true) ?: []) : $question->correct_answer }}" maxlength="2000"></div>
                <div><button class="panel-btn panel-btn-secondary" type="submit">ذخیره تغییرات</button></div>
            </form>
            <form method="POST" action="{{ route('admin.exams.questions.destroy', [$exam, $question]) }}" data-confirm="این سؤال حذف شود؟">
                @csrf @method('DELETE')
                <button class="panel-btn panel-btn-danger panel-btn-sm" type="submit">حذف سؤال</button>
            </form>
        </section>
    @empty
        <div class="panel-card panel-empty">هنوز سؤالی برای این آزمون طراحی نشده است.</div>
    @endforelse
@endsection
