@props(['label', 'name', 'checked' => false, 'hint' => null, 'wide' => false])
<label class="adm-switch-row {{ $wide ? 'wide' : '' }}" @if ($wide) style="grid-column:1/-1" @endif>
  <span><b>{{ $label }}</b>@if ($hint)<small>{{ $hint }}</small>@endif</span>
  <span class="adm-switch"><input type="hidden" name="{{ $name }}" value="0"><input type="checkbox" name="{{ $name }}" value="1" @checked(old($name, $checked))><i></i></span>
</label>
