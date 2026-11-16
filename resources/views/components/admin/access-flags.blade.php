{{-- What an enrollment / plan includes. flags: video, live, resources, exams, qa --}}
@props(['flags' => []])
@php $map = ['video' => ['video', 'ویدیوهای ضبط‌شده'], 'live' => ['live', 'کلاس زنده'], 'resources' => ['lib', 'جزوه و منابع'], 'exams' => ['exam', 'آزمون و تکلیف'], 'qa' => ['chat', 'پرسش و پاسخ']]; @endphp
<span class="adm-flags">@foreach ($map as $k => [$i, $t])<span class="adm-flag {{ in_array($k, $flags) ? 'is-on' : '' }}" title="{{ $t }}{{ in_array($k, $flags) ? '' : ' — ندارد' }}"><x-admin.icon :name="$i"/></span>@endforeach</span>
