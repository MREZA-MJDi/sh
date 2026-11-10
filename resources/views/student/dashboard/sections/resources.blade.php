<section class="page" id="s-res"><div class="card"><div class="ch"><h2>جزوات و منابع</h2><span class="tag">مرتبط با کلاس‌های ثبت‌نامی</span></div><div class="g3">
    @forelse($resources as $resource)<article class="card"><b>{{ $resource->title }}</b><p class="mu">{{ $resource->kind }} · {{ $resource->is_free ? 'رایگان' : $faNumber($resource->price).' تومان' }}</p>@if($resource->file_path)<a class="btn s" href="{{ route('student.resources.download', $resource) }}">دانلود</a>@endif</article>
    @empty<div class="empty">منبع منتشرشده‌ای برای کلاس‌های تو وجود ندارد.</div>@endforelse
</div></div></section>
