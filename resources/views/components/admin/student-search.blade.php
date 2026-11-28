@props(['selected' => null, 'multiple' => false])
<div class="adm-field wide" data-admin-student-search data-endpoint="{{ route('admin.lookups.students') }}" @if($multiple) data-multiple @endif>
  <label class="adm-label">
    دانش‌آموزان فعال
    <input type="search" class="adm-input" autocomplete="off" maxlength="80" placeholder="نام یا شماره همراه" data-query>
  </label>
  <button type="button" class="adm-btn adm-btn-line adm-btn-sm" data-find>جست‌وجو</button>
  <div class="adm-actions" data-matches aria-live="polite"></div>
  <div class="adm-actions" data-selected aria-live="polite">
    @foreach($multiple ? $selected : collect([$selected])->filter() as $student)
      <span class="adm-chip">
        <input type="hidden" name="{{ $multiple ? 'student_ids[]' : 'student_id' }}" value="{{ $student->id }}">
        {{ $student->name }}
        <button type="button" data-remove aria-label="حذف انتخاب {{ $student->name }}">×</button>
      </span>
    @endforeach
  </div>
  @error($multiple ? 'student_ids' : 'student_id')<span class="adm-err">{{ $message }}</span>@enderror
  @if($multiple) @error('student_ids.*')<span class="adm-err">{{ $message }}</span>@enderror @endif
</div>
