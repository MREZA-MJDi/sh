@extends('admin.layouts.app')
@section('title', 'تکالیف')

@section('content')
@php
  $classOptions = $classrooms->pluck('name', 'id')->all();
  $statusLabels = ['draft' => 'پیش‌نویس', 'published' => 'منتشرشده'];
  $submissionStatus = ['submitted' => 'ارسال‌شده', 'reviewed' => 'بررسی‌شده', 'late' => 'دیرهنگام'];
@endphp

<x-admin.page-head tone="track" icon="file" title="تکالیف و تمرین‌ها" desc="ساخت تکلیف برای گروه‌های آموزشی و بررسی پاسخ دانش‌آموزان">
  <x-admin.btn icon="plus" data-modal-open="homework-modal">تکلیف جدید</x-admin.btn>
</x-admin.page-head>

<div class="adm-stats">
  <x-admin.stat tone="track" icon="file" label="تکالیف" :value="number_format($homeworks->total())"/>
  <x-admin.stat tone="track" icon="clock" label="پاسخ‌های در انتظار" :value="number_format($submissions->getCollection()->where('status', 'submitted')->count())"/>
  <x-admin.stat tone="track" icon="check" label="پاسخ‌های بررسی‌شده" :value="number_format($submissions->getCollection()->where('status', 'reviewed')->count())"/>
  <x-admin.stat tone="track" icon="users" label="گروه‌های مجاز" :value="number_format($classrooms->count())"/>
</div>

<x-admin.panel title="تکالیف ثبت‌شده" flush>
  <div class="adm-table-wrap"><table class="adm-table">
    <thead><tr><th>تکلیف</th><th>کلاس</th><th>مهلت</th><th>پاسخ‌ها</th><th>وضعیت</th><th></th></tr></thead>
    <tbody>
      @forelse($homeworks as $homework)
        @php
          $fill = [
            'classroom_id' => (string) $homework->classroom_id,
            'title' => $homework->title,
            'instructions' => $homework->instructions,
            'due_at' => $homework->due_at?->format('Y-m-d\TH:i'),
            'status' => $homework->status,
          ];
        @endphp
        <tr>
          <td><b>{{ $homework->title }}</b><small>{{ $homework->creator?->name ?? '—' }}</small></td>
          <td>{{ $homework->classroom?->name ?? '—' }}</td>
          <td>{{ $homework->due_at?->format('Y/m/d H:i') ?? 'بدون مهلت' }}</td>
          <td class="adm-num">{{ $homework->submissions_count }} پاسخ · {{ $homework->pending_submissions_count }} در انتظار</td>
          <td><x-admin.badge :tone="$homework->status === 'published' ? 'ok' : 'warn'">{{ $statusLabels[$homework->status] ?? $homework->status }}</x-admin.badge></td>
          <td><div class="adm-actions">
            @if($homework->attachment_path)<x-admin.btn size="sm" variant="line" :href="route('admin.homework.attachment', $homework)">پیوست</x-admin.btn>@endif
            <x-admin.btn size="sm" variant="soft" icon="edit" data-modal-open="homework-modal" data-title="ویرایش تکلیف" :data-fill="json_encode($fill, JSON_UNESCAPED_UNICODE)" :data-action="route('admin.homework.update', $homework)" data-method="PATCH">ویرایش</x-admin.btn>
            <x-admin.btn size="sm" variant="bad" icon="trash" data-modal-open="homework-delete" :data-name="$homework->title" :data-action="route('admin.homework.destroy', $homework)"/>
          </div></td>
        </tr>
      @empty
        <tr><td colspan="6">تکلیفی در کلاس‌های مجاز ثبت نشده است.</td></tr>
      @endforelse
    </tbody>
  </table></div>
  <x-slot:footer>{{ $homeworks->links() }}</x-slot:footer>
</x-admin.panel>

<x-admin.panel title="پاسخ‌های دانش‌آموزان" desc="ثبت نمره و بازخورد برای پاسخ‌های ارسال‌شده" flush>
  <div class="adm-table-wrap"><table class="adm-table">
    <thead><tr><th>دانش‌آموز</th><th>تکلیف / کلاس</th><th>زمان ارسال</th><th>فایل‌ها</th><th>نمره و بازخورد</th><th>تصحیح</th></tr></thead>
    <tbody>
      @forelse($submissions as $submission)
        <tr>
          <td><b>{{ $submission->student?->name ?? '—' }}</b></td>
          <td>{{ $submission->homework?->title ?? '—' }}<small>{{ $submission->homework?->classroom?->name ?? '—' }}</small></td>
          <td>{{ $submission->submitted_at?->format('Y/m/d H:i') ?? '—' }}</td>
          <td>@forelse($submission->files as $file)<small>{{ $file->original_name }}</small>@empty<span class="adm-muted">بدون فایل</span>@endforelse</td>
          <td>{{ $submission->score === null ? '—' : number_format((float) $submission->score, 2).' از ۲۰' }}<small>{{ $submission->feedback ?: 'بدون بازخورد' }}</small></td>
          <td>
            <form method="POST" action="{{ route('admin.homework.review', $submission) }}" class="adm-stack">
              @csrf @method('PATCH')
              <input class="adm-input" type="number" name="score" min="0" max="20" step="0.25" value="{{ $submission->score }}" placeholder="نمره از ۲۰">
              <textarea class="adm-input" name="feedback" rows="2" maxlength="30000" placeholder="بازخورد مدرس" required>{{ $submission->feedback }}</textarea>
              <button type="submit" class="adm-btn adm-btn-line adm-btn-sm">ثبت بررسی</button>
            </form>
          </td>
        </tr>
      @empty
        <tr><td colspan="6">پاسخی برای بررسی ثبت نشده است.</td></tr>
      @endforelse
    </tbody>
  </table></div>
  <x-slot:footer>{{ $submissions->links() }}</x-slot:footer>
</x-admin.panel>
@endsection

@push('modals')
<x-admin.modal :demo="false" id="homework-modal" title="تکلیف جدید" size="lg" :action="route('admin.homework.store')">
  <x-admin.legend title="اطلاعات تکلیف"/>
  <x-admin.input label="عنوان تکلیف" name="title" wide required/>
  <x-admin.select label="کلاس / گروه" name="classroom_id" :options="$classOptions" required/>
  <x-admin.input label="مهلت تحویل" name="due_at" type="datetime-local"/>
  <x-admin.textarea label="دستورالعمل و توضیحات" name="instructions" wide/>
  <x-admin.input label="فایل پیوست" name="attachment" type="file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.zip"/>
  <x-admin.select label="وضعیت" name="status" :options="$statusLabels" required/>
</x-admin.modal>
<x-admin.confirm :demo="false" id="homework-delete" title="حذف تکلیف" text="تکلیفی که پاسخ دانش‌آموز داشته باشد حذف نمی‌شود تا سوابق حفظ شوند."/>
@endpush
