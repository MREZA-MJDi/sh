@props(['articles'])

@if($articles->isNotEmpty())
<section class="bg-white py-16 md:py-20" aria-labelledby="home-articles-title">
    <div class="mx-auto max-w-7xl px-5">
        <x-kit.layout.section-heading eyebrow="دانش و یادگیری" title="تازه‌ترین مقاله‌ها" description="مطالب آموزشی و نکته‌های کاربردی آکادمی." :href="route('seo.articles.index')" link-label="همه مقاله‌ها" />
        <div class="mt-9 grid gap-6 md:grid-cols-3">
            @foreach($articles as $article)
                <article class="overflow-hidden rounded-2xl border border-[#D9E0E4] bg-white transition hover:-translate-y-1 hover:shadow-lg">
                    @if($article->featured_image_path)
                        <a href="{{ route('seo.articles.show', $article->slug) }}">
                            <img src="{{ Storage::disk('public')->url($article->featured_image_path) }}" alt="{{ $article->image_alt ?: $article->title }}" class="aspect-[16/9] w-full object-cover" loading="lazy">
                        </a>
                    @endif
                    <div class="p-6">
                        <time class="text-xs text-[#0E7C66]" datetime="{{ $article->published_at->toAtomString() }}">{{ $article->published_at->format('Y/m/d') }}</time>
                        <h3 class="mt-3 text-lg font-black text-[#16202A]"><a href="{{ route('seo.articles.show', $article->slug) }}" class="transition hover:text-[#0E7C66]">{{ $article->title }}</a></h3>
                        @if($article->excerpt)<p class="mt-3 text-sm leading-7 text-[#4A5560]">{{ $article->excerpt }}</p>@endif
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</section>
@endif
