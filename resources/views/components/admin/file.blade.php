@props(['label', 'name', 'accept' => null, 'hint' => 'فایل را بکشید یا انتخاب کنید', 'wide' => true, 'icon' => 'up'])
<x-admin.field :label="$label" :name="$name" :wide="$wide" group>
  <label class="adm-upload">
    <input type="file" name="{{ $name }}" @if ($accept) accept="{{ $accept }}" @endif>
    <x-admin.icon :name="$icon"/><b>انتخاب فایل</b><small data-upload-name>{{ $hint }}</small>
  </label>
</x-admin.field>
