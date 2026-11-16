{{-- Label + control wrapper. group=true uses a <div> (for chips / several inputs) instead of a wrapping <label>. --}}
@props(['label', 'name' => null, 'hint' => null, 'wide' => false, 'required' => false, 'group' => false])
@php $tag = $group ? 'div' : 'label'; @endphp
<{{ $tag }} {{ $attributes->merge(['class' => 'adm-field'.($wide ? ' wide' : '')]) }} @if ($group) role="group" @endif>
  <span class="adm-label">{{ $label }}@if ($required)<i class="adm-req" title="الزامی">*</i>@endif</span>
  {{ $slot }}
  @if ($hint)<small>{{ $hint }}</small>@endif
  @if ($name)@error($name)<small class="adm-err">{{ $message }}</small>@enderror @endif
</{{ $tag }}>
