<section class="page" id="p-res"><div class="card"><div class="ch"><h2>منابع و محصولات</h2><span class="tag">{{ $faNumber($resource_count) }} منبع</span></div><div class="g3">
    @forelse($resources as $resource)<article class="card"><b>{{ $resource->title }}</b><p class="mu">{{ $resource->course?->title ?? $resource->classroom?->name ?? $resource->kind }}</p><span class="tag">{{ $resource->is_free ? 'رایگان' : $faNumber($resource->price).' تومان' }}</span>
        @if($resource->file_path && $selected_child)<a class="btn s" href="{{ route('guardian.resources.download', ['resource'=>$resource,'child'=>$selected_child->id]) }}">دریافت منبع</a>@endif
    </article>@empty<div class="empty">منبع آموزشی منتشرشده‌ای در دسترس نیست.</div>@endforelse
</div></div></section>
