@extends('admin.layouts.app')
@section('title', 'مدیریت کاربران')

@section('content')
@php
  $teacherView = $teacherView ?? false;
  $roleLabels = ['student' => 'دانش‌آموزان', 'teacher' => 'مدرسان', 'parent' => 'اولیا'];
  $rolePlural = ['student' => 'students', 'teacher' => 'teachers', 'parent' => 'parents'];
  $roleLabel = $roleLabels[$role] ?? 'کاربران';
  $classOptions = $classrooms->pluck('name', 'id')->all();
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
  <x-admin.stat tone="users" icon="check" label="فعال" :value="number_format($users->getCollection()->where('is_active', true)->count())" hint="در صفحه جاری"/>
  <x-admin.stat tone="users" icon="pause" label="غیرفعال" :value="number_format($users->getCollection()->where('is_active', false)->count())" hint="در صفحه جاری"/>
  <x-admin.stat tone="users" icon="user" label="نقش" :value="$roleLabel"/>
</div>

<x-admin.panel flush data-filter>
  <x-admin.toolbar placeholder="جستجوی نام، ایمیل یا شماره تماس…">
    <div class="adm-toolbar-end">
      <a class="adm-btn adm-btn-line adm-btn-sm" href="{{ route('admin.students.index') }}">دانش‌آموزان</a>
      @unless($teacherView)
        <a class="adm-btn adm-btn-line adm-btn-sm" href="{{ route('admin.teachers.index') }}">مدرسان</a>
        <a class="adm-btn adm-btn-line adm-btn-sm" href="{{ route('admin.parents.index') }}">اولیا</a>
      @endunless
    </div>
  </x-admin.toolbar>
  <div class="adm-grid adm-cols-2" data-filter-list>
    @forelse($users as $user)
      @php
        $associations = $role === 'student'
          ? $user->enrolledClasses->pluck('name')->join('، ')
          : ($role === 'teacher'
            ? number_format((int) $user->assigned_courses_count).' دوره'
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
          'child_ids' => $role === 'parent' ? $user->children->pluck('id')->map(fn ($id) => (string) $id)->all() : [],
          'child_ids_labels' => $role === 'parent' ? $user->children->pluck('name')->values()->all() : [],
        ];
        $updateAction = route('admin.'.$rolePlural[$role].'.update', $user);
      @endphp
      <article class="adm-card" data-name="{{ $user->name.' '.$user->email.' '.$user->phone.' '.$associations }}">
        <div class="adm-card-body">
          <div class="adm-card-title">
            <div class="adm-person">
              <span class="adm-avatar">
                @if($role === 'teacher' && $user->avatarUrl())
                  <img src="{{ $user->avatarUrl() }}" alt="{{ $user->name }}" loading="lazy">
                @else
                  {{ mb_substr($user->name, 0, 1) }}
                @endif
              </span>
              <div><b>{{ $user->name }}</b><small>شناسه {{ $user->id }} · عضویت {{ \App\Support\PersianDate::format($user->created_at, 'Y/m/d') }}</small></div>
            </div>
            <x-admin.badge :tone="$user->is_active ? 'ok' : 'warn'">{{ $user->is_active ? 'فعال' : 'غیرفعال' }}</x-admin.badge>
          </div>
          <div class="adm-meta">
            @if($user->email)<span><x-admin.icon name="msg"/><span class="adm-ltr">{{ $user->email }}</span></span>@endif
            @if($user->phone)<span><x-admin.icon name="phone"/>@phone($user->phone)</span>@endif
          </div>
          <div class="adm-panel-kicker">{{ $role === 'student' ? 'کلاس‌های آموزشی' : ($role === 'teacher' ? 'دوره‌های تخصیص‌یافته' : 'فرزندان مرتبط') }}</div>
          <p>{{ $associations ?: 'هنوز ارتباط آموزشی ثبت نشده است.' }}</p>
          <div class="adm-actions">
            @if($canManage)
              <x-admin.btn size="sm" variant="soft" icon="edit" data-modal-open="user-modal" data-title="ویرایش کاربر" :data-fill="json_encode($fill, JSON_UNESCAPED_UNICODE)" :data-action="$updateAction" data-method="PATCH">ویرایش پرونده</x-admin.btn>
              <form method="POST" action="{{ route('admin.users.status', $user) }}">
                @csrf @method('PATCH')
                <input type="hidden" name="is_active" value="{{ $user->is_active ? 0 : 1 }}">
                <button type="submit" class="adm-btn adm-btn-line adm-btn-sm">{{ $user->is_active ? 'غیرفعال‌سازی' : 'فعال‌سازی' }}</button>
              </form>
            @endif
          </div>
        </div>
      </article>
    @empty
    @endforelse
  </div>
  @if($users->isEmpty())
    <x-admin.empty text="حسابی در این نقش ثبت نشده است."/>
  @else
    <x-admin.empty data-empty class="adm-hide" text="کاربری با این مشخصات پیدا نشد."/>
  @endif
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
    <p class="adm-muted wide">دسترسی‌های پایه از نقش مدرس دریافت می‌شود. تخصیص دوره و کلاس از بخش‌های آموزشی مدیریت می‌شود.</p>
  @else
    <div class="wide" data-student-lookup data-remote-lookup data-endpoint="{{ route('admin.lookups.students') }}">
      <label class="adm-label" for="parent-student-search">جستجوی فرزند بر اساس نام یا شماره تماس</label>
      <div class="adm-actions">
        <input id="parent-student-search" type="search" class="adm-input" data-student-lookup-input minlength="2" maxlength="80" placeholder="حداقل دو حرف وارد کنید">
        <button type="button" class="adm-btn adm-btn-line" data-student-lookup-search>جستجو</button>
      </div>
      <div data-student-lookup-results aria-live="polite"></div>
      <div data-student-lookup-selected data-array-field="child_ids" class="adm-actions"></div>
      @foreach($selectedChildren as $child)
        <input type="hidden" name="child_ids[]" value="{{ $child->id }}" data-generated-selection="true">
        <span class="adm-chip" data-generated-selection="true">{{ $child->name }}</span>
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
  const chosen = new Set();
  const syncChosen = () => {
    chosen.clear();
    root.querySelectorAll('input[name="child_ids[]"]').forEach((el) => chosen.add(String(el.value)));
  };

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
    syncChosen();
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
