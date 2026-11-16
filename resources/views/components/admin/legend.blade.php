{{-- Section heading inside a form grid --}}
@props(['title', 'desc' => null])
<div class="adm-legend"><h4>{{ $title }}</h4>@if ($desc)<p>{{ $desc }}</p>@endif</div>
