@extends('admin.layouts.app')
@section('title', 'مقاله‌های سئو')

@section('content')
@php
  $statusLabels = ['draft' => 'پیش‌نویس', 'scheduled' => 'زمان‌بندی‌شده', 'published' => 'منتشرشده', 'archived' => 'بایگانی'];
  $statusTones = ['draft' => 'warn', 'scheduled' => 'info', 'published' => 'ok', 'archived' => 'mute'];
@endphp
<x-admin.page-head tone="shop" icon="file" title="مقاله‌ها و محتوای سئو" desc="انتشار محتوای ساختاریافته، متادیتا و تصویر Open Graph">
  <x-admin.btn icon="plus" data-modal-open="article-modal">ایجاد مقاله جدید</x-admin.btn>
</x-admin.page-head>

<div class="adm-stats">
  <x-admin.stat tone="shop" icon="file" label="کل مقاله‌ها" :value="number_format($articles->total())"/>
  <x-admin.stat tone="shop" icon="check" label="منتشرشده" :value="number_format($publishedArticleCount)"/>
  <x-admin.stat tone="shop" icon="edit" label="پیش‌نویس" :value="number_format($draftArticleCount)"/>
  <x-admin.stat tone="shop" icon="clock" label="زمان‌بندی‌شده" :value="number_format($scheduledArticleCount)"/>
</div>

<x-admin.panel flush>
  <div class="adm-table-wrap"><table class="adm-table">
    <thead><tr><th>تصویر</th><th>مقاله</th><th>نویسنده</th><th>وضعیت</th><th>انتشار</th><th>نشانی</th><th>اقدام</th></tr></thead>
    <tbody>
      @forelse($articles as $article)
        @php
          $fill = [
            'title' => $article->title,
            'slug' => $article->slug,
            'excerpt' => $article->excerpt,
            'body' => $article->body,
            'meta_title' => $article->meta_title,
            'meta_description' => $article->meta_description,
            'keywords' => implode(', ', $article->keywords ?? []),
            'canonical_url' => $article->canonical_url,
            'image_alt' => $article->image_alt,
            'image_position' => $article->image_position ?: '50% 50%',
            'status' => $article->status,
            'published_at' => $article->published_at?->format('Y-m-d\TH:i'),
          ];
        @endphp
        <tr>
          <td>@if($article->featured_image_path)<img src="{{ Storage::disk('public')->url($article->featured_image_path) }}" alt="{{ $article->image_alt ?: $article->title }}" loading="lazy" style="width:88px;height:56px;object-fit:cover;border-radius:8px">@else<span class="adm-muted">بدون تصویر</span>@endif</td>
          <td><b>{{ $article->title }}</b><small>{{ \Illuminate\Support\Str::limit($article->excerpt, 90) }}</small></td>
          <td>{{ $article->author?->name ?? '—' }}</td>
          <td><x-admin.badge :tone="$statusTones[$article->status] ?? 'mute'">{{ $statusLabels[$article->status] ?? $article->status }}</x-admin.badge></td>
          <td>{{ $article->published_at?->format('Y/m/d H:i') ?? '—' }}</td>
          <td><a href="{{ route('seo.articles.show', $article->slug) }}" target="_blank" rel="noopener">مشاهده</a><small>/articles/{{ $article->slug }}</small></td>
          <td><div class="adm-actions">
            <x-admin.btn size="sm" variant="soft" icon="edit" data-modal-open="article-modal" data-title="ویرایش مقاله" :data-fill="json_encode($fill, JSON_UNESCAPED_UNICODE)" :data-action="route('admin.seo.articles.update', $article)" data-method="PATCH">ویرایش</x-admin.btn>
            <x-admin.btn size="sm" variant="bad" icon="trash" data-modal-open="article-delete" :data-name="$article->title" :data-action="route('admin.seo.articles.destroy', $article)"/>
          </div></td>
        </tr>
      @empty
        <tr><td colspan="7">مقاله‌ای ثبت نشده است.</td></tr>
      @endforelse
    </tbody>
  </table></div>
  <x-slot:footer>{{ $articles->links() }}</x-slot:footer>
</x-admin.panel>
@endsection

@push('modals')
<x-admin.modal :demo="false" id="article-modal" title="ایجاد مقاله جدید" size="lg" :action="route('admin.seo.articles.store')">
  <x-admin.legend title="محتوا"/>
  <x-admin.input label="عنوان مقاله" name="title" wide required/>
  <x-admin.input label="Slug (اختیاری)" name="slug"/>
  <x-admin.input label="تاریخ انتشار" name="published_at" type="datetime-local"/>
  <x-admin.textarea label="خلاصه مقاله" name="excerpt" wide/>
  <x-admin.textarea label="متن مقاله (HTML مجاز و امن‌سازی می‌شود)" name="body" wide rows="12" required hint="برای ساختار بهتر سئو از تیترهای H2 و H3، پاراگراف‌های کوتاه، فهرست‌ها و لینک‌های مرتبط استفاده کنید."/>
  <x-admin.legend title="بهینه‌سازی موتور جست‌وجو"/>
  <x-admin.input label="عنوان متا" name="meta_title"/>
  <x-admin.input label="توضیحات متا" name="meta_description" wide/>
  <x-admin.input label="کلمات کلیدی" name="keywords" wide/>
  <x-admin.input label="Canonical URL" name="canonical_url" type="url" wide/>
  <x-admin.input label="تصویر شاخص مقاله" name="featured_image" type="file" accept="image/jpeg,image/png,image/webp" data-preview-ratio="16 / 9" data-preview-fit="cover" hint="پیش‌نمایش با نسبت کارت‌های وبلاگ و تصویر اصلی مقاله نمایش داده می‌شود."/>
  <x-admin.input label="متن جایگزین تصویر" name="image_alt"/>
  <x-admin.input label="موقعیت تصویر شاخص (افقی و عمودی)" name="image_position" placeholder="50% 50%"/>
  <x-admin.input label="تصویر اشتراک‌گذاری Open Graph" name="og_image" type="file" accept="image/jpeg,image/png,image/webp" data-preview-ratio="1200 / 630" data-preview-fit="cover" hint="نسبت پیشنهادی برای اشتراک‌گذاری در شبکه‌های اجتماعی ۱۲۰۰×۶۳۰ است."/>
  <x-admin.select label="وضعیت" name="status" :options="$statusLabels" required/>
</x-admin.modal>
<x-admin.confirm :demo="false" id="article-delete" title="حذف مقاله" text="مقاله و تصاویر مرتبط از فضای عمومی حذف می‌شوند."/>
@endpush
