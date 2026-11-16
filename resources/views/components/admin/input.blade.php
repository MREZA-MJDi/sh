@props(['label', 'name', 'type' => 'text', 'value' => null, 'hint' => null, 'wide' => false, 'required' => false, 'placeholder' => null, 'addon' => null])
@php $ltr = in_array($type, ['email', 'tel', 'url', 'password', 'number']) || $attributes->has('ltr'); @endphp
<x-admin.field :label="$label" :name="$name" :hint="$hint" :wide="$wide" :required="$required">
  @if ($addon)<span class="adm-input-group">@endif
  <input class="adm-input" type="{{ $type }}" name="{{ $name }}" value="{{ old($name, $value) }}" @if ($placeholder) placeholder="{{ $placeholder }}" @endif @if ($ltr) dir="ltr" @endif @required($required) {{ $attributes->except('ltr') }}>
  @if ($addon)<span class="adm-addon">{{ $addon }}</span></span>@endif
</x-admin.field>
