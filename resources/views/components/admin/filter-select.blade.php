{{-- <x-admin.filter-select key="role" placeholder="همه نقش‌ها" :options="['student'=>'دانش‌آموز']"/> ; rows need data-role="student" --}}
@props(['key', 'options', 'placeholder' => 'همه'])
<select class="adm-select" data-filter-select="{{ $key }}" aria-label="{{ $placeholder }}">
  <option value="">{{ $placeholder }}</option>
  @foreach ($options as $v => $label)<option value="{{ $v }}">{{ $label }}</option>@endforeach
</select>
