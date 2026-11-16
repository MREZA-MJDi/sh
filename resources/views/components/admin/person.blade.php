@props(['name', 'sub' => null])
<div class="adm-person"><x-admin.avatar :name="$name"/><div><b>{{ $name }}</b>@if ($sub)<small @if (preg_match('/[@0-9]/', $sub)) class="adm-ltr" @endif>{{ $sub }}</small>@endif</div></div>
