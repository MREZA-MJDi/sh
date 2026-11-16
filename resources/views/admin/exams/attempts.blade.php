@extends('admin.layouts.app')
@section('title', 'تلاش‌های آزمون')

@section('content')
<x-admin.page-head tone="track" icon="check" :title="'تلاش‌های '.$exam->title" desc="بازبینی پاسخ‌های تشریحی و ثبت نمره مدرس"
  :back-url="route('admin.exams.index')" back-label="آزمون‌ها">
  <x-admin.btn variant="line" icon="chart" :href="route('admin.exams.results', $exam)">کارنامه نتایج</x-admin.btn>
</x-admin.page-head>

<x-admin.panel title="تلاش‌های نیازمند بازبینی" :desc="$attempts->total().' تلاش برای این آزمون'" flush>
  <div class="adm-stack">
    @forelse($attempts as $attempt)
      <article class="adm-card">
        <div class="adm-card-body">
          <div class="adm-card-title">
            <div>
              <h3>{{ $attempt->student?->name ?? 'دانش‌آموز حذف‌شده' }}</h3>
              <p>تلاش {{ $attempt->attempt_number }} · {{ $attempt->submitted_at?->format('Y/m/d H:i') ?? 'زمان ثبت نشده' }}</p>
            </div>
            <x-admin.badge :tone="$attempt->status === 'graded' ? 'ok' : 'warn'">{{ $attempt->status === 'graded' ? 'تصحیح‌شده' : 'در انتظار تصحیح' }}</x-admin.badge>
          </div>

          @foreach($attempt->answers as $answer)
            @php
              $answerValue = $answer->answer;
              $decodedAnswer = is_string($answerValue) ? json_decode($answerValue, true) : null;
              if (is_array($decodedAnswer)) $answerValue = implode('، ', $decodedAnswer);
            @endphp
            <div class="adm-question-review">
              <b>{{ $answer->question?->question ?? 'سؤال حذف‌شده' }}</b>
              <p>{{ $answerValue ?: 'بدون پاسخ' }}</p>
              <small class="adm-muted">
                {{ $answer->question?->type === 'text' ? 'پاسخ تشریحی' : 'پاسخ تستی' }}
                · نمره سؤال: {{ $answer->question?->score ?? '—' }}
                · نمره ثبت‌شده: {{ $answer->score ?? '—' }}
              </small>
            </div>
          @endforeach

          @if($attempt->status === 'pending_review')
            <form method="POST" action="{{ route('admin.exams.attempts.grade', [$exam, $attempt]) }}" class="adm-form">
              @csrf
              @method('PATCH')
              @foreach($attempt->answers->filter(fn ($answer) => $answer->question?->type === 'text') as $answer)
                <x-admin.input
                  :label="'نمره پاسخ تشریحی: '.\Illuminate\Support\Str::limit($answer->question?->question ?? 'سؤال', 55)"
                  :name="'scores['.$answer->id.']'"
                  type="number" min="0" :max="$answer->question?->score ?? 100" step="0.01" required/>
              @endforeach
              <x-admin.textarea label="بازخورد مدرس" name="feedback" wide/>
              <div class="wide"><button type="submit" class="adm-btn adm-btn-primary">ثبت تصحیح و نمره</button></div>
            </form>
          @else
            <p class="adm-muted">تصحیح توسط {{ $attempt->reviewer?->name ?? 'مدرس' }} در {{ $attempt->reviewed_at?->format('Y/m/d H:i') }} انجام شده است.</p>
            @if($attempt->feedback)<p>{{ $attempt->feedback }}</p>@endif
          @endif
        </div>
      </article>
    @empty
      <x-admin.empty text="تلاشی برای بازبینی در این آزمون وجود ندارد."/>
    @endforelse
  </div>
  @if($attempts->hasPages())<x-slot:footer>{{ $attempts->links() }}</x-slot:footer>@endif
</x-admin.panel>
@endsection
