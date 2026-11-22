@extends('admin.layouts.app')
@section('title', 'جلسات اولیا و مدرس')

@section('content')
@include('admin.partials.communication-center-link')
@php
  $classOptions = $classrooms->pluck('name', 'id')->all();
  $statusLabels = ['requested' => 'درخواست‌شده', 'proposed' => 'پیشنهاد مدرس', 'scheduled' => 'زمان‌بندی‌شده', 'completed' => 'انجام‌شده', 'declined' => 'ردشده', 'cancelled' => 'لغوشده'];
  $isTeacher = auth()->user()?->hasRole('teacher') ?? false;
@endphp

<x-admin.page-head tone="comm" icon="calendar" title="جلسات اولیا و مدرس" desc="هماهنگی گفت‌وگوی آموزشی با دانش‌آموز، ولی و مدرس">
  <x-admin.btn icon="plus" data-modal-open="parent-meeting-modal">ثبت جلسه</x-admin.btn>
</x-admin.page-head>

<div class="adm-stats">
  <x-admin.stat tone="comm" icon="calendar" label="کل جلسات" :value="number_format($stats['total'])"/>
  <x-admin.stat tone="comm" icon="clock" label="درخواست / پیشنهاد" :value="number_format($stats['pending'])"/>
  <x-admin.stat tone="comm" icon="check" label="زمان‌بندی‌شده" :value="number_format($stats['scheduled'])"/>
  <x-admin.stat tone="comm" icon="users" label="کلاس‌های فعال در دسترس" :value="number_format($classroomCount)"/>
</div>

<x-admin.panel flush>
  <div class="adm-table-wrap"><table class="adm-table">
    <thead><tr><th>موضوع جلسه</th><th>دانش‌آموز و ولی</th><th>کلاس / دوره</th><th>زمان</th><th>وضعیت</th><th>پیگیری</th></tr></thead>
    <tbody>
      @forelse($meetings as $meeting)
        <tr id="parent-meeting-{{ $meeting->id }}">
          <td><b>{{ $meeting->title }}</b><small>{{ \Illuminate\Support\Str::limit($meeting->message, 100) }}</small></td>
          <td>{{ $meeting->student?->name ?? '—' }}<small>ولی: {{ $meeting->guardian?->name ?? '—' }}</small></td>
          <td>{{ $meeting->classroom?->name ?? 'بدون کلاس' }}<small>{{ $meeting->course?->title ?? '—' }}</small></td>
          <td>{{ ($meeting->starts_at ? \App\Support\PersianDate::format($meeting->starts_at, 'Y/m/d H:i') : 'زمان تعیین نشده') }}<small>{{ ($meeting->ends_at ? \App\Support\PersianDate::format($meeting->ends_at, 'H:i') : '') }}</small></td>
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

@push('scripts')
<script>
(() => {
 const button = document.querySelector('[data-parent-meeting-search]');
 const input = document.querySelector('#parent-meeting-student-search');
 const classroom = document.querySelector('#parent-meeting-classroom');
 const student = document.querySelector('#parent-meeting-student');
 const guardian = document.querySelector('#parent-meeting-guardian');
 const status = document.querySelector('[data-parent-meeting-status]');
 if (!button || !input || !student || !guardian || !status) return;

 const reset = () => {
  student.replaceChildren(new Option('ابتدا جستجو کنید', ''));
  guardian.replaceChildren(new Option('ابتدا دانش‌آموز را انتخاب کنید', ''));
  guardian.disabled = true;
 };
 const fillGuardians = () => {
  const selected = student.selectedOptions[0];
  let guardians = [];
  try { guardians = JSON.parse(selected?.dataset.guardians || '[]'); } catch (error) {}
  guardian.replaceChildren(new Option('انتخاب ولی دانش‌آموز', ''));
  guardians.forEach((item) => guardian.add(new Option(item.name, item.id)));
  guardian.disabled = guardians.length === 0;
  if (!guardians.length && student.value) status.textContent = 'برای این دانش‌آموز ولی فعال ثبت نشده است.';
 };
 const search = async () => {
  const query = input.value.trim();
  if (query.length < 2) {
   status.textContent = 'برای جستجو حداقل دو حرف وارد کنید.';
   input.focus();
   return;
  }
  if (button.dataset.requiresClass === '1' && !classroom?.value) {
   status.textContent = 'ابتدا کلاس موردنظر را انتخاب کنید.';
   classroom?.focus();
   return;
  }

  button.disabled = true;
  reset();
  status.textContent = 'در حال جستجوی دانش‌آموزان…';
  try {
   const params = new URLSearchParams({q: query});
   if (classroom?.value) params.set('classroom_id', classroom.value);
   const response = await fetch(button.dataset.endpoint + '?' + params.toString(), {
    headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
    credentials: 'same-origin'
   });
   const payload = await response.json();
   if (!response.ok) throw new Error(payload.message || 'جستجوی دانش‌آموزان انجام نشد.');
   (payload.data || []).forEach((item) => {
    const option = new Option(item.name, item.id);
    option.dataset.guardians = JSON.stringify(item.guardians || []);
    student.add(option);
   });
   status.textContent = payload.data?.length
    ? payload.data.length + ' دانش‌آموز پیدا شد.'
    : 'دانش‌آموزی با این مشخصات در محدوده انتخاب‌شده پیدا نشد.';
  } catch (error) {
   status.textContent = error.message || 'خطا در جستجوی دانش‌آموز.';
  } finally {
   button.disabled = false;
  }
 };

 button.addEventListener('click', search);
 input.addEventListener('keydown', (event) => {
  if (event.key === 'Enter') { event.preventDefault(); search(); }
 });
 student.addEventListener('change', fillGuardians);
 classroom?.addEventListener('change', () => {
  reset();
  status.textContent = 'کلاس تغییر کرد؛ دوباره دانش‌آموز را جستجو کنید.';
 });
 guardian.disabled = true;
})();
</script>
@endpush

@push('modals')
<x-admin.modal :demo="false" id="parent-meeting-modal" title="ثبت جلسه با ولی" size="lg" :action="route('admin.parent-meetings.store')">
  <x-admin.legend title="افراد و موضوع جلسه"/>
  <x-admin.select id="parent-meeting-classroom" label="کلاس مرتبط" name="classroom_id" :options="$classOptions" :required="$isTeacher" placeholder="بدون کلاس / جلسه مستقل"/>
  <x-admin.input id="parent-meeting-student-search" label="جستجوی دانش‌آموز" name="student_search" type="search" wide placeholder="نام یا شماره تماس را وارد کنید"/>
  <button type="button" class="adm-btn adm-btn-line" data-parent-meeting-search data-endpoint="{{ route('admin.parent-meetings.lookup') }}" data-requires-class="{{ $isTeacher ? '1' : '0' }}">جستجو</button>
  <p class="adm-muted" data-parent-meeting-status aria-live="polite">برای نمایش دانش‌آموزان، حداقل دو حرف جستجو کنید.</p>
  <x-admin.select id="parent-meeting-student" label="دانش‌آموز" name="student_id" :options="[]" required placeholder="ابتدا جستجو و انتخاب کنید"/>
  <x-admin.select id="parent-meeting-guardian" label="ولی دانش‌آموز" name="guardian_id" :options="[]" required placeholder="ابتدا دانش‌آموز را انتخاب کنید"/>
  <x-admin.input label="موضوع جلسه" name="title" wide required/>
  <x-admin.textarea label="توضیحات" name="message" wide/>
  <x-admin.legend title="زمان‌بندی"/>
  <x-admin.input label="شروع جلسه" name="starts_at" type="datetime-local" required/>
  <x-admin.input label="پایان جلسه" name="ends_at" type="datetime-local"/>
</x-admin.modal>
@endpush
