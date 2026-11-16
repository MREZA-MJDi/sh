{{-- options: [value => label] --}}
@props(['label', 'name', 'options', 'selected' => null, 'hint' => null, 'wide' => false, 'required' => false, 'placeholder' => null])
<x-admin.field :label="$label" :name="$name" :hint="$hint" :wide="$wide" :required="$required">
  <select class="adm-select" name="{{ $name }}" @required($required) {{ $attributes }}>
    @if ($placeholder)<option value="">{{ $placeholder }}</option>@endif
    @foreach ($options as $v => $text)<option value="{{ $v }}" @selected((string) old($name, $selected) === (string) $v)>{{ $text }}</option>@endforeach
  </select>
</x-admin.field>
