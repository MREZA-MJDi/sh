@extends('admin.layouts.app')
@section('title', 'مدیریت کاربران')

@section('content')
@php
  $teacherView = $teacherView ?? false;
  $roleLabels = ['student' => 'دانش‌آموزان', 'teacher' => 'مدرسان', 'parent' => 'اولیا'];
  $rolePlural = ['student' => 'students', 'teacher' => 'teachers', 'parent' => 'parents'];
  $roleLabel = $roleLabels[$role] ?? 'کاربران';
  $classOptions = $classrooms->pluck('name', 'id')->all();
  $courseOptions = $courses->pluck('title', 'id')->all();
  $teacherAccessOptions = collect($teacherAccessGroups)->mapWithKeys(fn ($group, $key) => [$key => $group['label']])->all();
  $createAction = route('admin.'.$rolePlural[$role].'.store');
  $canManage = ! $teacherView && auth()->user()->hasPermissionTo('users.manage');
@endphp

<x-admin.page-head tone="users" icon="users" :title="$roleLabel" desc="مدیریت حساب‌ها، وضعیت و ارتباط آموزشی">
  @if($canManage)
    <x-admin.btn icon="plus" data-modal-open="user-modal">{{ $role === 'teacher' ? 'ساخت مدرس' : ($role === 'student' ? 'افزودن دانش‌آموز' : 'افزودن ولی') }}</x-admin.btn>
  @endif
</x-admin.page-head>

<div class="adm-stats">
  <x-admin.stat tone="users" icon="users" label="کل حساب‌ها" :value="number_format($users->total())"/>
  <x-admin.stat tone="users" icon="check" label="فعال" :value="number_format($users->getCollection()->where('is_active', true)->count())"/>
  <x-admin.stat tone="users" icon="pause" label="غیرفعال" :value="number_format($users->getCollection()->where('is_active', false)->count())"/>
  <x-admin.stat tone="users" icon="user" label="نقش" :value="$roleLabel"/>
</div>

<x-admin.panel flush>
  <x-admin.toolbar placeholder="جستجوی نام، ایمیل یا شماره تماس…">
    <div class="adm-toolbar-end">
      <a class="adm-btn adm-btn-line adm-btn-sm" href="{{ route('admin.students.index') }}">دانش‌آموزان</a>
      @unless($teacherView)
        <a class="adm-btn adm-btn-line adm-btn-sm" href="{{ route('admin.teachers.index') }}">مدرسان</a>
        <a class="adm-btn adm-btn-line adm-btn-sm" href="{{ route('admin.parents.index') }}">اولیا</a>
      @endunless
    </div>
  </x-admin.toolbar>
  <div class="adm-table-wrap"><table class="adm-table">
    <thead><tr><th>کاربر</th><th>راه تماس</th><th>ارتباط آموزشی</th><th>وضعیت</th><th>اقدام</th></tr></thead>
    <tbody>
      @forelse($users as $user)
        @php
          $associations = $role === 'student'
            ? $user->enrolledClasses->pluck('name')->join('، ')
            : ($role === 'teacher'
              ? $user->assignedCourses->pluck('title')->join('، ')
              : $user->children->pluck('name')->join('، '));
          $fill = [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'grade' => $user->grade,
            'subject' => $user->subject,
            'profile_note' => $user->profile_note,
            'status' => $user->is_active ? 'active' : 'pending',
            'classroom_ids' => $role === 'student' ? $user->enrolledClasses->pluck('id')->map(fn ($id) => (string) $id)->all() : [],
            'course_ids' => $role === 'teacher' ? $user->assignedCourses->pluck('id')->map(fn ($id) => (string) $id)->all() : [],
            'teacher_access' => $role === 'teacher' ? collect($teacherSelectedGroups[$user->id] ?? [])->filter()->keys()->values()->all() : [],
            'child_ids' => $role === 'parent' ? $user->children->pluck('id')->map(fn ($id) => (string) $id)->all() : [],
          ];
          $updateAction = route('admin.'.$rolePlural[$role].'.update', $user);
        @endphp
        <tr>
          <td><b>{{ $user->name }}</b><small>#{{ $user->id }} · {{ $user->created_at?->format('Y/m/d') }}</small></td>
          <td>{{ $user->email ?: '—' }}<small>{{ $user->phone ?: '—' }}</small></td>
          <td>{{ \Illuminate\Support\Str::limit($associations ?: 'بدون تخصیص', 90) }}</td>
          <td><x-admin.badge :tone="$user->is_active ? 'ok' : 'warn'">{{ $user->is_active ? 'فعال' : 'غیرفعال' }}</x-admin.badge></td>
          <td><div class="adm-actions">
            @if($canManage)
              <x-admin.btn size="sm" variant="soft" icon="edit" data-modal-open="user-modal" data-title="ویرایش کاربر" :data-fill="json_encode($fill, JSON_UNESCAPED_UNICODE)" :data-action="$updateAction" data-method="PATCH">ویرایش</x-admin.btn>
            @endif
            @if($canManage)
              <form method="POST" action="{{ route('admin.users.status', $user) }}">
                @csrf @method('PATCH')
                <input type="hidden" name="is_active" value="{{ $user->is_active ? 0 : 1 }}">
                <button type="submit" class="adm-btn adm-btn-line adm-btn-sm">{{ $user->is_active ? 'غیرفعال‌سازی' : 'فعال‌سازی' }}</button>
              </form>
            @endif
          </div></td>
        </tr>
      @empty
        <tr><td colspan="5">حسابی در این نقش ثبت نشده است.</td></tr>
      @endforelse
    </tbody>
  </table></div>
  <x-slot:footer>{{ $users->links() }}</x-slot:footer>
</x-admin.panel>
@endsection

@push('modals')
@if($canManage)
<x-admin.modal :demo="false" id="user-modal" title="ایجاد حساب جدید" size="lg" :action="$createAction">
  <x-admin.legend title="اطلاعات حساب"/>
  <x-admin.input label="نام و نام خانوادگی" name="name" wide required/>
  <x-admin.input label="ایمیل" name="email" type="email" :required="$role !== 'teacher'"/>
  <x-admin.input label="شماره تماس" name="phone"/>
  @if($role === 'student')
    <x-admin.input label="پایه تحصیلی" name="grade" required/>
    <x-admin.input label="شناسه دانش‌آموزی (اختیاری)" name="external_student_id"/>
    <x-admin.chips label="عضویت در کلاس‌های فعال" name="classroom_ids" :options="$classOptions"/>
  @elseif($role === 'teacher')
    <x-admin.input label="درس / تخصص" name="subject" required/>
    <x-admin.textarea label="یادداشت پروفایل" name="profile_note" wide/>
    <x-admin.select label="وضعیت حساب" name="status" :options="['active' => 'فعال', 'pending' => 'در انتظار تأیید']" required/>
    <x-admin.chips label="دوره‌های تخصیص‌یافته" name="course_ids" :options="$courseOptions"/>
    <x-admin.chips label="دسترسی‌ها" name="teacher_access" :options="$teacherAccessOptions"/>
    <div class="wide"><b>گروه‌های دسترسی مدرس</b><ul class="adm-list">@foreach($teacherAccessGroups as $group)<li>{{ $group['label'] }}</li>@endforeach</ul></div>
  @else
    <div class="wide" data-student-lookup data-remote-lookup data-endpoint="{{ route('admin.lookups.students') }}">
      <label class="adm-label" for="parent-student-search">جستجوی فرزند بر اساس نام یا شماره تماس</label>
      <div class="adm-actions">
        <input id="parent-student-search" type="search" class="adm-input" data-student-lookup-input minlength="2" maxlength="80" placeholder="حداقل دو حرف وارد کنید">
        <button type="button" class="adm-btn adm-btn-line" data-student-lookup-search>جستجو</button>
      </div>
      <div data-student-lookup-results aria-live="polite"></div>
      <div data-student-lookup-selected class="adm-actions"></div>
      @foreach($selectedChildren as $child)
        <input type="hidden" name="child_ids[]" value="{{ $child->id }}">
        <span class="adm-chip">{{ $child->name }}</span>
      @endforeach
      <p class="adm-muted">حداقل دو حرف وارد کنید. فهرست کامل دانش‌آموزان بارگذاری نمی‌شود؛ فقط نتیجه جستجوی شما و حداکثر ۲۰ مورد نمایش داده می‌شود.</p>
    </div>
  @endif
  <x-admin.legend title="امنیت حساب"/>
  <x-admin.input label="گذرواژه اولیه (حداقل ۱۲ کاراکتر)" name="password" type="password" required autocomplete="new-password"/>
  <x-admin.input label="تکرار گذرواژه" name="password_confirmation" type="password" required autocomplete="new-password"/>
</x-admin.modal>
@endif

@if($role === 'parent' && $canManage)
<script>
(() => {
  const root = document.querySelector('[data-student-lookup]');
  if (!root) return;
  const input = root.querySelector('[data-student-lookup-input]');
  const search = root.querySelector('[data-student-lookup-search]');
  const results = root.querySelector('[data-student-lookup-results]');
  const selected = root.querySelector('[data-student-lookup-selected]');
  const endpoint = root.dataset.endpoint;
  const chosen = new Set(Array.from(root.querySelectorAll('input[name="child_ids[]"]')).map((el) => el.value));

  const addStudent = (student) => {
    if (chosen.has(String(student.id))) return;
    chosen.add(String(student.id));
    const hidden = document.createElement('input');
    hidden.type = 'hidden';
    hidden.name = 'child_ids[]';
    hidden.value = student.id;
    const chip = document.createElement('span');
    chip.className = 'adm-chip';
    chip.textContent = student.label + (student.meta ? ' · ' + student.meta : '');
    const remove = document.createElement('button');
    remove.type = 'button';
    remove.className = 'adm-btn adm-btn-line adm-btn-sm';
    remove.textContent = 'حذف';
    remove.addEventListener('click', () => {
      chosen.delete(String(student.id));
      hidden.remove();
      chip.remove();
      remove.remove();
    });
    selected.append(hidden, chip, remove);
  };

  search?.addEventListener('click', async () => {
    const query = input?.value?.trim() || '';
    if (query.length < 2) {
      results.textContent = 'برای جستجو حداقل دو حرف وارد کنید.';
      return;
    }
    results.textContent = 'در حال جستجو…';
    try {
      const response = await fetch(endpoint + '?q=' + encodeURIComponent(query), {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin'
      });
      if (!response.ok) throw new Error('جستجو انجام نشد.');
      const payload = await response.json();
      results.replaceChildren();
      if (!payload.data.length) {
        results.textContent = 'دانش‌آموزی پیدا نشد.';
        return;
      }
      payload.data.forEach((student) => {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'adm-btn adm-btn-line adm-btn-sm';
        button.textContent = student.label + (student.meta ? ' · ' + student.meta : '');
        button.disabled = chosen.has(String(student.id));
        button.addEventListener('click', () => {
          addStudent(student);
          button.disabled = true;
        });
        results.append(button);
      });
      if (payload.has_more) {
        const more = document.createElement('small');
        more.textContent = 'بیش از ۲۰ نتیجه وجود دارد؛ جستجو را دقیق‌تر کنید.';
        results.append(more);
      }
    } catch (error) {
      results.textContent = error.message || 'خطا در جستجوی دانش‌آموز.';
    }
  });
})();
</script>
@endif

@endpush
