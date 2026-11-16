@props(['label', 'name', 'value' => null, 'hint' => null, 'wide' => true, 'required' => false, 'rows' => 4, 'placeholder' => null])
<x-admin.field :label="$label" :name="$name" :hint="$hint" :wide="$wide" :required="$required">
  <textarea class="adm-textarea" name="{{ $name }}" rows="{{ $rows }}" @if ($placeholder) placeholder="{{ $placeholder }}" @endif @required($required)>{{ old($name, $value) }}</textarea>
</x-admin.field>
