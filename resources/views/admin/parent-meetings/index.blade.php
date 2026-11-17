@extends('admin.layouts.app')
@section('title', 'جلسات اولیا و مدرس')

@section('content')
@php
  $classOptions = $classrooms->pluck('name', 'id')->all();
  $studentOptions = $students->pluck('name', 'id')->all();
  $guardianOptions = $students->flatMap(fn ($student) => $student->guardians)->unique('id')->pluck('name', 'id')->all();
  $statusLabels = ['requested' => 'درخواست‌شده', 'proposed' => 'پیشنهاد مدرس', 'scheduled' => 'زمان‌بندی‌شده', 'completed' => 'انجام‌شده', 'declined' => 'ردشده', 'cancelled' => 'لغوشده'];
  $isTeacher = auth()->user()?->hasRole('teacher') ?? false;
@endphp

<x-admin.page-head tone="comm" icon="calendar" title="جلسات اولیا و مدرس" desc="هماهنگی گفت‌وگوی آموزشی با دانش‌آموز، ولی و مدرس">
  <x-admin.btn icon="plus" data-modal-open="parent-meeting-modal">ثبت جلسه</x-admin.btn>
</x-admin.page-head>

<div class="adm-stats">
  <x-admin.stat tone="comm" icon="calendar" label="کل جلسات" :value="number_format($meetings->total())"/>
  <x-admin.stat tone="comm" icon="clock" label="درخواست / پیشنهاد" :value="number_format($meetings->getCollection()->whereIn('status', ['requested', 'proposed'])->count())"/>
  <x-admin.stat tone="comm" icon="check" label="زمان‌بندی‌شده" :value="number_format($meetings->getCollection()->where('status', 'scheduled')->count())"/>
  <x-admin.stat tone="comm" icon="users" label="کلاس‌های فعال در دسترس" :value="number_format($classrooms->count())"/>
</div>

<x-admin.panel flush>
  <div class="adm-table-wrap"><table class="adm-table">
    <thead><tr><th>موضوع جلسه</th><th>دانش‌آموز و ولی</th><th>کلاس / دوره</th><th>زمان</th><th>وضعیت</th><th>پیگیری</th></tr></thead>
    <tbody>
      @forelse($meetings as $meeting)
        <tr>
          <td><b>{{ $meeting->title }}</b><small>{{ \Illuminate\Support\Str::limit($meeting->message, 100) }}</small></td>
          <td>{{ $meeting->student?->name ?? '—' }}<small>ولی: {{ $meeting->guardian?->name ?? '—' }}</small></td>
          <td>{{ $meeting->classroom?->name ?? 'بدون کلاس' }}<small>{{ $meeting->course?->title ?? '—' }}</small></td>
          <td>{{ $meeting->starts_at?->format('Y/m/d H:i') ?? 'زمان تعیین نشده' }}<small>{{ $meeting->ends_at?->format('H:i') ?? '' }}</small></td>
          <td><x-admin.badge :tone="in_array($meeting->status, ['completed', 'scheduled'], true) ? 'ok' : 'warn'">{{ $statusLabels[$meeting->status] ?? $meeting->status }}</x-admin.badge></td>
          <td>
            @if(!$isTeacher && in_array($meeting->status, ['requested', 'proposed', 'scheduled'], true))
              <form method="POST" action="{{ route('admin.parent-meetings.status', $meeting) }}" class="adm-stack">
                @csrf @method('PATCH')
                <select name="status" class="adm-select" required>
                  @if(in_array($meeting->status, ['requested', 'proposed'], true))
                    <option value="scheduled">تأیید و زمان‌بندی</option>
                    <option value="declined">رد درخواست</option>
                    <option value="cancelled">لغو</option>
                  @else
                    <option value="completed">انجام شد</option>
                    <option value="cancelled">لغو</option>
                  @endif
                </select>
                @if(in_array($meeting->status, ['requested', 'proposed'], true))
                  <input type="datetime-local" name="starts_at" class="adm-input" value="{{ $meeting->starts_at?->format('Y-m-d\TH:i') }}" required>
                  <input type="datetime-local" name="ends_at" class="adm-input" value="{{ $meeting->ends_at?->format('Y-m-d\TH:i') }}">
                @endif
                <button type="submit" class="adm-btn adm-btn-line adm-btn-sm">ثبت وضعیت</button>
              </form>
            @else
              <span class="adm-muted">{{ $meeting->creator?->name ?? '—' }}</span>
            @endif
          </td>
        </tr>
      @empty
        <tr><td colspan="6">جلسه‌ای ثبت نشده است.</td></tr>
      @endforelse
    </tbody>
  </table></div>
  <x-slot:footer>{{ $meetings->links() }}</x-slot:footer>
</x-admin.panel>
@endsection

@push('modals')
<x-admin.modal :demo="false" id="parent-meeting-modal" title="ثبت جلسه با ولی" size="lg" :action="route('admin.parent-meetings.store')">
  <x-admin.legend title="افراد و موضوع جلسه"/>
  <x-admin.select label="کلاس مرتبط" name="classroom_id" :options="$classOptions" :required="$isTeacher" placeholder="بدون کلاس / جلسه مستقل"/>
  <x-admin.select label="دانش‌آموز" name="student_id" :options="$studentOptions" required/>
  <x-admin.select label="ولی دانش‌آموز" name="guardian_id" :options="$guardianOptions" required/>
  <x-admin.input label="موضوع جلسه" name="title" wide required/>
  <x-admin.textarea label="توضیحات" name="message" wide/>
  <x-admin.legend title="زمان‌بندی"/>
  <x-admin.input label="شروع جلسه" name="starts_at" type="datetime-local" required/>
  <x-admin.input label="پایان جلسه" name="ends_at" type="datetime-local"/>
</x-admin.modal>
@endpush
