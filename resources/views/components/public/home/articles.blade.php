@props(['articles'])

@if($articles->isNotEmpty())
<section class="bg-white py-16 md:py-20" aria-labelledby="home-articles-title">
    <div class="mx-auto max-w-7xl px-5">
        <div class="flex items-end justify-between gap-4">
            <div>
                <span class="text-sm font-bold text-indigo-600">دانش و یادگیری</span>
                <h2 id="home-articles-title" class="mt-2 text-3xl font-black">تازه‌ترین مقاله‌ها</h2>
            </div>
            <a href="{{ route('seo.articles.index') }}" class="text-sm font-bold text-indigo-700">همه مقاله‌ها ←</a>
        </div>
        <div class="mt-9 grid gap-6 md:grid-cols-3">
            @foreach($articles as $article)
                <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                    @if($article->featured_image_path)
                        <a href="{{ route('seo.articles.show', $article->slug) }}">
                            <img src="{{ Storage::disk('public')->url($article->featured_image_path) }}" alt="{{ $article->image_alt ?: $article->title }}" class="aspect-[16/9] w-full object-cover" loading="lazy">
                        </a>
                    @endif
                    <div class="p-6">
                        <time class="text-xs text-slate-400" datetime="{{ $article->published_at->toAtomString() }}">{{ $article->published_at->format('Y/m/d') }}</time>
                        <h3 class="mt-3 text-lg font-black"><a href="{{ route('seo.articles.show', $article->slug) }}" class="hover:text-indigo-700">{{ $article->title }}</a></h3>
                        @if($article->excerpt)<p class="mt-3 text-sm leading-7 text-slate-500">{{ $article->excerpt }}</p>@endif
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</section>
@endif
