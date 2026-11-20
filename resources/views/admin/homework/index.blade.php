@extends('admin.layouts.app')
@section('title', 'تکالیف')

@section('content')
@php
  $classOptions = $classrooms->pluck('name', 'id')->all();
  $statusLabels = ['draft' => 'پیش‌نویس', 'published' => 'منتشرشده'];
  $submissionStatus = ['submitted' => 'ارسال‌شده', 'reviewed' => 'بررسی‌شده', 'late' => 'دیرهنگام'];
@endphp

<x-admin.page-head tone="track" icon="file" title="تکالیف و تمرین‌ها" desc="تکلیف بساز، پاسخ‌های دانش‌آموزان را ببین و بازخورد ثبت کن.">
  <x-admin.btn icon="plus" data-modal-open="homework-modal">ساخت تکلیف</x-admin.btn>
</x-admin.page-head>

<div class="adm-stats">
  <x-admin.stat tone="track" icon="file" label="کل تکالیف" :value="number_format($homeworks->total())" hint="در کلاس‌های مجاز"/>
  <x-admin.stat tone="track" icon="clock" label="در انتظار بررسی" :value="number_format($submissions->getCollection()->where('status', 'submitted')->count())" hint="در این صفحه از پاسخ‌ها"/>
  <x-admin.stat tone="edu" icon="check" label="بررسی‌شده" :value="number_format($submissions->getCollection()->where('status', 'reviewed')->count())" hint="در این صفحه از پاسخ‌ها"/>
  <x-admin.stat tone="users" icon="users" label="کلاس‌های در دسترس" :value="number_format($classrooms->count())" hint="امکان ساخت تکلیف"/>
</div>

<x-admin.panel title="فیلتر تکالیف" desc="برای پیدا کردن تکلیف‌ها و پاسخ‌های یک بازه‌ی مشخص، تاریخ را انتخاب کن." data-tone="track">
  <form method="GET" action="{{ route('admin.homework.index') }}" class="adm-toolbar">
    <label class="adm-label">از تاریخ <input class="adm-input" type="date" name="from" value="{{ $filters['from'] ?? '' }}"></label>
    <label class="adm-label">تا تاریخ <input class="adm-input" type="date" name="to" value="{{ $filters['to'] ?? '' }}"></label>
    <div class="adm-toolbar-end">
      <x-admin.btn type="submit" size="sm">اعمال فیلتر</x-admin.btn>
      <x-admin.btn :href="route('admin.homework.index')" variant="line" size="sm">پاک‌کردن</x-admin.btn>
    </div>
  </form>
</x-admin.panel>

<x-admin.panel title="تکالیف کلاس‌ها" desc="هر کارت وضعیت تکلیف، مهلت تحویل و تعداد پاسخ‌ها را نشان می‌دهد.">
  @if($homeworks->isNotEmpty())
    <div class="adm-grid adm-cols-2">
      @foreach($homeworks as $homework)
        @php
          $fill = [
            'classroom_id' => (string) $homework->classroom_id,
            'title' => $homework->title,
            'instructions' => $homework->instructions,
            'due_at' => $homework->due_at?->format('Y-m-d\TH:i'),
            'status' => $homework->status,
          ];
          $isPublished = $homework->status === 'published';
        @endphp
        <article class="adm-card">
          <div class="adm-card-body">
            <div class="adm-card-title">
              <div><h3>{{ $homework->title }}</h3><p>{{ $homework->classroom?->name ?? 'کلاس مشخص نشده' }}</p></div>
              <x-admin.badge :tone="$isPublished ? 'ok' : 'warn'">{{ $statusLabels[$homework->status] ?? $homework->status }}</x-admin.badge>
            </div>
            <div class="adm-meta">
              <span><x-admin.icon name="user"/>{{ $homework->creator?->name ?? 'ثبت‌کننده نامشخص' }}</span>
              <span><x-admin.icon name="clock"/>{{ $homework->due_at ? \App\Support\PersianDate::format($homework->due_at, 'Y/m/d H:i') : 'بدون مهلت' }}</span>
            </div>
            <div class="adm-work-item" data-tone="track">
              <span><x-admin.icon name="file"/></span>
              <div><b>{{ number_format($homework->submissions_count) }} پاسخ دریافت‌شده</b><small>تعداد پاسخ‌های منتظر بررسی</small></div>
              <strong>{{ number_format($homework->pending_submissions_count) }}</strong>
            </div>
            <div class="adm-actions">
              @if($homework->attachment_path)
                <x-admin.btn size="sm" variant="line" :href="route('admin.homework.attachment', $homework)">دانلود پیوست</x-admin.btn>
              @endif
              <x-admin.btn size="sm" variant="soft" icon="edit" data-modal-open="homework-modal" data-title="ویرایش تکلیف" :data-fill="json_encode($fill, JSON_UNESCAPED_UNICODE)" :data-action="route('admin.homework.update', $homework)" data-method="PATCH">ویرایش</x-admin.btn>
              <x-admin.btn size="sm" variant="bad" icon="trash" data-modal-open="homework-delete" :data-name="$homework->title" :data-action="route('admin.homework.destroy', $homework)"/>
            </div>
          </div>
        </article>
      @endforeach
    </div>
  @else
    <div class="adm-queue-empty"><x-admin.icon name="file"/><b>هنوز تکلیفی ثبت نشده</b><span>با ساخت اولین تکلیف، دانش‌آموزان کلاس می‌توانند پاسخشان را ارسال کنند.</span></div>
  @endif
  <x-slot:footer>{{ $homeworks->links() }}</x-slot:footer>
</x-admin.panel>

<x-admin.panel title="صف بررسی پاسخ‌ها" desc="پاسخ هر دانش‌آموز را باز کن، نمره بده و بازخورد روشن و قابل‌فهم ثبت کن." flush>
  @if($submissions->isNotEmpty())
    <div class="adm-review-list">
      @foreach($submissions as $submission)
        <article class="adm-review-card">
          <div class="adm-review-main">
            <div class="adm-review-person">
              <span class="adm-roster-avatar" aria-hidden="true">{{ mb_substr($submission->student?->name ?? '؟', 0, 1) }}</span>
              <div>
                <b>{{ $submission->student?->name ?? 'دانش‌آموز نامشخص' }}</b>
                <small>{{ $submission->homework?->title ?? 'تکلیف حذف‌شده' }} · {{ $submission->homework?->classroom?->name ?? 'کلاس نامشخص' }}</small>
              </div>
              <x-admin.badge :tone="$submission->status === 'reviewed' ? 'ok' : ($submission->status === 'late' ? 'warn' : 'info')">{{ $submissionStatus[$submission->status] ?? $submission->status }}</x-admin.badge>
            </div>
            <div class="adm-review-meta">
              <span>زمان ارسال: {{ $submission->submitted_at ? \App\Support\PersianDate::format($submission->submitted_at, 'Y/m/d H:i') : '—' }}</span>
              @if($submission->score !== null)
                <span class="adm-review-score">نمره ثبت‌شده: <strong>{{ number_format((float) $submission->score, 2) }}</strong> از ۲۰</span>
              @endif
            </div>
            @if($submission->feedback)
              <div class="adm-panel-kicker">بازخورد قبلی: {{ $submission->feedback }}</div>
            @endif
            <div class="adm-review-files">
              @forelse($submission->files as $file)
                <a href="{{ route('admin.homework-submission-files.download', $file) }}"><x-admin.icon name="file"/>{{ $file->original_name }}</a>
              @empty
                <span class="adm-panel-kicker">دانش‌آموز فایلی پیوست نکرده است.</span>
              @endforelse
            </div>
          </div>
          <div>
            @if(auth()->user()->hasPermissionTo('homework.grade'))
              <form method="POST" action="{{ route('admin.homework.review', $submission) }}" class="adm-review-form">
                @csrf @method('PATCH')
                <label class="adm-label">نمره از ۲۰
                  <input class="adm-input" type="number" name="score" min="0" max="20" step="0.25" value="{{ $submission->score }}" placeholder="مثلاً ۱۸">
                </label>
                <label class="adm-label">بازخورد به دانش‌آموز
                  <textarea class="adm-input" name="feedback" rows="3" maxlength="30000" placeholder="نقاط قوت و موارد قابل بهبود را بنویس…" required>{{ $submission->feedback }}</textarea>
                </label>
                <button type="submit" class="adm-btn adm-btn-primary"><x-admin.icon name="check"/>ثبت ارزیابی</button>
              </form>
            @else
              <div class="adm-queue-empty">دسترسی ثبت نمره برای این حساب فعال نیست.</div>
            @endif
          </div>
        </article>
      @endforeach
    </div>
  @else
    <div class="adm-review-empty"><x-admin.icon name="check"/><b>صف بررسی خالی است</b><p>وقتی دانش‌آموزان پاسخ تکلیف را ارسال کنند، برای بررسی در این بخش قرار می‌گیرند.</p></div>
  @endif
  <x-slot:footer>{{ $submissions->links() }}</x-slot:footer>
</x-admin.panel>
@endsection

@push('modals')
<x-admin.modal :demo="false" id="homework-modal" title="تکلیف جدید" size="lg" :action="route('admin.homework.store')">
  <x-admin.legend title="اطلاعات تکلیف"/>
  <x-admin.input label="عنوان تکلیف" name="title" wide required/>
  <x-admin.select label="کلاس / گروه" name="classroom_id" :options="$classOptions" required/>
  <x-admin.input label="مهلت تحویل" name="due_at" type="datetime-local" :min="now()->format('Y-m-d\\TH:i')" step="60" hint="تاریخ و ساعت را از تقویم انتخاب کن؛ تکلیف منتشرشده برای دانش‌آموزان فعال همین کلاس نمایش داده می‌شود."/>
  <x-admin.textarea label="دستورالعمل و توضیحات" name="instructions" wide/>
  <x-admin.input label="فایل پیوست" name="attachment" type="file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.zip"/>
  <x-admin.select label="وضعیت" name="status" :options="$statusLabels" required/>
</x-admin.modal>
<x-admin.confirm :demo="false" id="homework-delete" title="حذف تکلیف" text="تکلیفی که پاسخ دانش‌آموز داشته باشد حذف نمی‌شود تا سوابق حفظ شوند."/>
@endpush
