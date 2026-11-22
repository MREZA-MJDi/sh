@extends('admin.layouts.app')
@section('title', 'سؤال‌های آزمون')

@section('content')
@php
  $types = ['single' => 'تک‌گزینه‌ای', 'multiple' => 'چندگزینه‌ای', 'text' => 'تشریحی'];
@endphp

<x-admin.page-head tone="track" icon="file" :title="'سؤال‌های '.$exam->title" desc="مدیریت سؤال‌های موتور داخلی شیخان و مسیر جایگزین Quiz24"
  :back-url="route('admin.exams.index')" back-label="آزمون‌ها">
  <x-admin.btn icon="plus" data-modal-open="question-modal">افزودن سؤال</x-admin.btn>
  <x-admin.btn variant="line" icon="eye" :href="route('admin.exams.results', $exam)">نتایج</x-admin.btn>
</x-admin.page-head>

@if($exam->provider === 'quiz24')
  <x-admin.panel title="مسیر جایگزین محلی فعال است" desc="تا زمان تأیید اتصال خارجی، سؤال‌های همین صفحه برای اجرای آزمون در شیخان استفاده می‌شوند." data-tone="edu"/>
@endif

<x-admin.panel title="فهرست سؤال‌ها" :desc="$exam->questions->count().' سؤال · '.$exam->questions->sum('score').' نمره کل'" flush>
  <div class="adm-stack">
    @forelse($exam->questions as $question)
      @php
        $correctAnswer = $question->correct_answer;
        if ($question->type === 'multiple') {
          $decoded = json_decode((string) $correctAnswer, true);
          if (is_array($decoded)) $correctAnswer = implode(', ', $decoded);
        }
        $fill = [
          'type' => $question->type,
          'question' => $question->question,
          'options_text' => implode("\n", $question->options ?? []),
          'correct_answer' => $correctAnswer,
          'score' => $question->score,
          'sort_order' => $question->sort_order,
        ];
      @endphp
      <article class="adm-card">
        <div class="adm-card-body">
          <div class="adm-card-title">
            <div><h3>{{ $loop->iteration }}. {{ $question->question }}</h3><p>{{ $types[$question->type] ?? $question->type }} · {{ $question->score }} نمره</p></div>
            <div class="adm-actions">
              <x-admin.btn size="sm" variant="soft" icon="edit" data-modal-open="question-modal" data-title="ویرایش سؤال" :data-fill="json_encode($fill, JSON_UNESCAPED_UNICODE)" :data-action="route('admin.exams.questions.update', [$exam, $question])" data-method="PATCH">ویرایش</x-admin.btn>
              <x-admin.btn size="sm" variant="bad" icon="trash" data-modal-open="question-delete" :data-name="$question->question" :data-action="route('admin.exams.questions.destroy', [$exam, $question])" aria-label="حذف"/>
            </div>
          </div>
          @if($question->options)
            <ol class="adm-list">
              @foreach($question->options as $option)
                <li>{{ $option }}</li>
              @endforeach
            </ol>
          @endif
          @if($question->type !== 'text')
            <small class="adm-muted">پاسخ صحیح: {{ $correctAnswer }}</small>
          @endif
        </div>
      </article>
    @empty
      <x-admin.empty text="هنوز سؤالی ثبت نشده است. برای انتشار آزمون، سؤال‌ها را اضافه کنید."/>
    @endforelse
  </div>
</x-admin.panel>
@endsection

@push('modals')
<x-admin.modal :demo="false" id="question-modal" title="سؤال جدید" size="lg" :action="route('admin.exams.questions.store', $exam)">
  <x-admin.legend title="متن و نوع سؤال"/>
  <x-admin.select label="نوع سؤال" name="type" :options="$types" required/>
  <x-admin.input label="نمره سؤال" name="score" type="number" min="0.01" step="0.01" required/>
  <x-admin.textarea label="متن سؤال" name="question" wide required/>
  <x-admin.textarea label="گزینه‌ها (هر گزینه در یک خط؛ برای تشریحی خالی بماند)" name="options_text" wide/>
  <x-admin.input label="پاسخ صحیح" name="correct_answer" wide placeholder="تک‌گزینه‌ای: متن دقیق گزینه · چندگزینه‌ای: متن گزینه‌ها با کاما"/>
  <x-admin.input label="ترتیب نمایش" name="sort_order" type="number" min="0"/>
</x-admin.modal>
<x-admin.confirm :demo="false" id="question-delete" title="حذف سؤال" text="اگر برای آزمون تلاشی ثبت شده باشد، حذف سؤال مجاز نیست تا سوابق دانش‌آموز حفظ شود."/>
@endpush
