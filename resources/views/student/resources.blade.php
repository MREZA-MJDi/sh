@extends('student.layouts.app')
@section('title', 'جزوه‌ها و منابع')
@section('content')
<section class="page" id="resources"><div class="card"><div class="chead"><h2>جزوه‌ها و منابع</h2><span class="pill">{{ ($resources ?? collect())->count() }} منبع</span></div>
 <div class="g3">
  @forelse (($resources ?? collect()) as $resource)
   <div class="card cc"><div class="th"><svg class="i"><use href="{{ url('student/i-file') }}"/></svg></div><h3>{{ $resource->title }}</h3><p>{{ $resource->course?->title ?? $resource->classroom?->name ?? 'منبع عمومی' }} · {{ $resource->kind }}</p>
    @if ($resource->file_path)<a class="btn s" href="{{ route('student.resources.download', $resource) }}">دریافت منبع</a>@else<span class="tag">فایل ندارد</span>@endif
   </div>
  @empty
   <p>منبع منتشرشده‌ای برای شما وجود ندارد.</p>
  @endforelse
 </div>
</div></section>
@endsection