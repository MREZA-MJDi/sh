{{-- Checkbox group (multiple=true → name[]) or radio group (multiple=false). options: [value => label] --}}
@props(['label', 'name', 'options', 'checked' => [], 'multiple' => true, 'hint' => null, 'wide' => true, 'scroll' => false])
<x-admin.field :label="$label" :name="$name" :hint="$hint" :wide="$wide" group>
  <div class="adm-chips {{ $scroll ? 'is-scroll' : '' }}">
    @foreach ($options as $v => $text)
      <label class="adm-chip"><input type="{{ $multiple ? 'checkbox' : 'radio' }}" name="{{ $name }}{{ $multiple ? '[]' : '' }}" value="{{ $v }}" @checked(in_array((string) $v, array_map('strval', (array) $checked), true))><span>{{ $text }}</span></label>
    @endforeach
  </div>
</x-admin.field>
