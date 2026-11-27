@props(['label', 'name', 'type' => 'text', 'value' => null, 'hint' => null, 'wide' => false, 'required' => false, 'placeholder' => null, 'addon' => null])
@php
  $phone = in_array($name, ['phone', 'contact_phone'], true) || $type === 'tel';
  $ltr = $phone || in_array($type, ['email', 'url', 'password', 'number']) || $attributes->has('ltr');
@endphp
<x-admin.field :label="$label" :name="$name" :hint="$hint" :wide="$wide" :required="$required">
  @if ($addon)<span class="adm-input-group">@endif
  <input class="adm-input" type="{{ $type }}" name="{{ $name }}" @unless(in_array($type, ['password', 'file'], true)) value="{{ old($name, $value) }}" @endunless @if ($placeholder) placeholder="{{ $placeholder }}" @endif @if ($ltr) dir="ltr" @endif @if ($phone) inputmode="tel" data-phone-input @endif @required($required) {{ $attributes->except('ltr') }}>
  @if ($addon)<span class="adm-addon">{{ $addon }}</span></span>@endif
</x-admin.field>
