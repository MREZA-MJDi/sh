@php
    $gradients = [
        'linear-gradient(135deg,#0d9488,#0f766e)',
        'linear-gradient(135deg,#2563eb,#1d4ed8)',
        'linear-gradient(135deg,#f97316,#ea580c)',
        'linear-gradient(135deg,#7c3aed,#6d28d9)',
        'linear-gradient(135deg,#059669,#047857)',
        'linear-gradient(135deg,#d97706,#b45309)'
    ];
    $bgGrad = $gradients[$article->id % count($gradients)];
@endphp
<a class="card cc" href="{{ route('seo.articles.show', $article->slug) }}">
    <div class="th" style="background:{{ $bgGrad }}">
        @if($article->featured_image_path)
            <img src="{{ Storage::disk('public')->url($article->featured_image_path) }}" alt="{{ $article->image_alt ?: $article->title }}" style="object-position: {{ $article->image_position ?? '50% 50%' }}" loading="lazy">
        @else
            <svg class="i"><use href="#pen"/></svg>
        @endif
        <span class="bd">{{ !empty($article->keywords) ? $article->keywords[0] : 'آموزشی' }}</span>
    </div>
    <div class="cb">
        <h3>{{ $article->title }}</h3>
        <p style="font-size:13px;color:var(--mute);line-height:1.7;margin:0 0 12px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden">{{ $article->excerpt }}</p>
        <div class="mt">
            <span><svg class="i" style="width:13px;height:13px;vertical-align:middle;margin-left:3px"><use href="#clock"/></svg>{{ $article->published_at ? $article->published_at->format('Y/m/d') : '' }}</span>
            <span>{{ $article->author->name ?? 'شیخان' }}</span>
        </div>
    </div>
</a>
