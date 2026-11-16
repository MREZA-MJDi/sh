@extends('admin.layouts.app')
@section('title', 'مدیریت کاربران')

@section('content')
@php
  $roleLabels = ['student' => 'دانش‌آموزان', 'teacher' => 'مدرسان', 'parent' => 'اولیا'];
  $rolePlural = ['student' => 'students', 'teacher' => 'teachers', 'parent' => 'parents'];
  $roleLabel = $roleLabels[$role] ?? 'کاربران';
  $classOptions = $classrooms->pluck('name', 'id')->all();
  $courseOptions = $courses->pluck('title', 'id')->all();
  $teacherAccessOptions = collect($teacherAccessGroups)->mapWithKeys(fn ($group, $key) => [$key => $group['label']])->all();
  $createAction = route('admin.'.$rolePlural[$role].'.store');
@endphp

<x-admin.page-head tone="users" icon="users" :title="$roleLabel" desc="مدیریت حساب‌ها، وضعیت و ارتباط آموزشی">
  <x-admin.btn icon="plus" data-modal-open="user-modal">افزودن {{ $role === 'student' ? 'دانش‌آموز' : ($role === 'teacher' ? 'مدرس' : 'ولی') }}</x-admin.btn>
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
            <x-admin.btn size="sm" variant="soft" icon="edit" data-modal-open="user-modal" data-title="ویرایش کاربر" :data-fill="json_encode($fill, JSON_UNESCAPED_UNICODE)" :data-action="$updateAction" data-method="PATCH">ویرایش</x-admin.btn>
            <form method="POST" action="{{ route('admin.users.status', $user) }}">
              @csrf @method('PATCH')
              <input type="hidden" name="is_active" value="{{ $user->is_active ? 0 : 1 }}">
              <button type="submit" class="adm-btn adm-btn-line adm-btn-sm">{{ $user->is_active ? 'غیرفعال‌سازی' : 'فعال‌سازی' }}</button>
            </form>
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
    <x-admin.chips label="گروه‌های دسترسی" name="teacher_access" :options="$teacherAccessOptions"/>
  @else
    <p class="adm-muted" style="grid-column:1/-1">برای اتصال فرزندان، شناسه حساب دانش‌آموز را در فیلدهای زیر وارد کنید. فهرست کامل دانش‌آموزان به‌صورت پیش‌فرض بارگذاری نمی‌شود.</p>
    <x-admin.input label="شناسه دانش‌آموز اول" name="child_ids[]" type="number"/>
    <x-admin.input label="شناسه دانش‌آموز دوم" name="child_ids[]" type="number"/>
    <x-admin.input label="شناسه دانش‌آموز سوم" name="child_ids[]" type="number"/>
  @endif
  <x-admin.legend title="امنیت حساب"/>
  <x-admin.input label="گذرواژه اولیه (حداقل ۱۲ کاراکتر)" name="password" type="password" required autocomplete="new-password"/>
  <x-admin.input label="تکرار گذرواژه" name="password_confirmation" type="password" required autocomplete="new-password"/>
</x-admin.modal>
@endpush
