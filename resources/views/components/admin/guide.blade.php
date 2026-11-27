{{-- Short "how this page works" steps: <x-admin.guide tone="track" :steps="['…','…']"/> --}}
@props(['steps', 'tone' => 'home'])
<ol class="adm-guide" data-tone="{{ $tone }}">@foreach ($steps as $step)<li>{{ $step }}</li>@endforeach</ol>
