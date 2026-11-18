@extends('admin.layouts.app')
@section('title', 'مدیریت آزمون و معما')

@section('content')
@include('admin.seo.partials.workspace-tabs')
<x-admin.page-head tone="shop" icon="help" title="آزمون و معماهای صفحه اصلی" desc="مدیریت سؤال‌های بخش دوم صفحه اصلی؛ ترتیب، نوع سؤال، پاسخ و توضیح پاسخ را کنترل کنید">
  <x-admin.btn icon="plus" data-modal-open="quiz-question-modal">افزودن سؤال</x-admin.btn>
</x-admin.page-head>

<div class="adm-stats">
  <x-admin.stat tone="shop" icon="help" label="کل سؤال‌ها" :value="number_format($questions->total())"/>
  <x-admin.stat tone="shop" icon="check" label="منتشرشده در سایت" :value="number_format($publishedCount)"/>
  <x-admin.stat tone="shop" icon="edit" label="پیش‌نویس" :value="number_format($draftCount)"/>
</div>

<x-admin.panel flush>
  <div class="adm-table-wrap"><table class="adm-table">
    <thead><tr><th>ترتیب</th><th>سؤال</th><th>نوع</th><th>دسته‌بندی</th><th>وضعیت</th><th>اقدام</th></tr></thead>
    <tbody>
    @forelse($questions as $question)
      @php
        $fill = [
          'category' => $question->category,
          'type' => $question->type,
          'question' => $question->question,
          'option_a' => data_get($question->options, 'A', ''),
          'option_b' => data_get($question->options, 'B', ''),
          'option_c' => data_get($question->options, 'C', ''),
          'option_d' => data_get($question->options, 'D', ''),
          'correct_answer' => $question->correct_answer,
          'explanation' => $question->explanation,
          'sort_order' => $question->sort_order,
          'status' => $question->status,
        ];
      @endphp
      <tr>
        <td>{{ $question->sort_order }}</td>
        <td><b>{{ $question->question }}</b><small>{{ $question->explanation }}</small></td>
        <td>{{ $question->type === 'riddle' ? 'معما / پاسخ متنی' : 'آزمون چهارگزینه‌ای' }}</td>
        <td>{{ $question->category }}</td>
        <td><x-admin.badge :tone="$question->status === 'published' ? 'ok' : 'mute'">{{ $question->status === 'published' ? 'منتشرشده' : 'پیش‌نویس' }}</x-admin.badge></td>
        <td><div class="adm-actions">
          <x-admin.btn size="sm" variant="soft" icon="edit" data-modal-open="quiz-question-modal" data-title="ویرایش سؤال" :data-fill="json_encode($fill, JSON_UNESCAPED_UNICODE)" :data-action="route('admin.seo.quiz.update', $question)" data-method="PATCH">ویرایش</x-admin.btn>
          <x-admin.btn size="sm" variant="bad" icon="trash" data-modal-open="quiz-question-delete" :data-name="$question->question" :data-action="route('admin.seo.quiz.destroy', $question)"/>
        </div></td>
      </tr>
    @empty
      <tr><td colspan="6">هنوز سؤالی ثبت نشده است.</td></tr>
    @endforelse
    </tbody>
  </table></div>
  <x-slot:footer>{{ $questions->links() }}</x-slot:footer>
</x-admin.panel>
@endsection

@push('modals')
<x-admin.modal :demo="false" id="quiz-question-modal" title="افزودن سؤال جدید" size="lg" :action="route('admin.seo.quiz.store')">
  <x-admin.legend title="متن سؤال"/>
  <x-admin.input label="دسته‌بندی" name="category" placeholder="مثلاً ریاضی، فیزیک، معمای منطقی" required/>
  <x-admin.select label="نوع سؤال" name="type" :options="['multiple_choice' => 'آزمون چهارگزینه‌ای', 'riddle' => 'معما با پاسخ متنی']" required/>
  <x-admin.textarea label="متن سؤال یا معما" name="question" wide rows="4" required/>
  <x-admin.legend title="گزینه‌ها (فقط برای آزمون چهارگزینه‌ای)"/>
  <x-admin.input label="گزینه A" name="option_a"/>
  <x-admin.input label="گزینه B" name="option_b"/>
  <x-admin.input label="گزینه C" name="option_c"/>
  <x-admin.input label="گزینه D" name="option_d"/>
  <x-admin.input label="پاسخ صحیح" name="correct_answer" required hint="برای آزمون چهارگزینه‌ای یکی از A/B/C/D و برای معما، متن پاسخ را وارد کنید."/>
  <x-admin.textarea label="توضیح پاسخ" name="explanation" wide rows="3"/>
  <x-admin.input label="ترتیب نمایش" name="sort_order" type="number" min="0" :value="0" required/>
  <x-admin.select label="وضعیت" name="status" :options="['published' => 'منتشرشده', 'draft' => 'پیش‌نویس']" required/>
</x-admin.modal>
<x-admin.confirm :demo="false" id="quiz-question-delete" title="حذف سؤال" text="این سؤال از آزمون صفحه اصلی حذف خواهد شد."/>
@endpush
