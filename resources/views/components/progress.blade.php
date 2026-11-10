@props(['value' => 0])
<div class="prog" role="progressbar" aria-valuenow="{{ $value }}" aria-valuemin="0" aria-valuemax="100"><i style="width:{{ $value }}%"></i></div>
