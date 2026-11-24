@extends('parent.layouts.app')
@section('title', 'منابع آموزشی')
@section('content')
<x-portal.page-head icon="book" tone="education" title="منابع و جزوه‌ها"/>
@include('parent.partials.kid-picker')
<div class="g g3">
    @forelse(($resources ?? collect()) as $resource)
        @php $purchase = $resource->purchases->first(); @endphp
        <article class="c parent-resource-card">
            <div class="portal-state-icon"><x-portal.icon :name="$resource->kind === 'book' ? 'book' : 'file'"/></div><h3>{{ $resource->title }}</h3>
            <p>{{ $resource->course?->title ?? $resource->classroom?->name ?? 'منبع عمومی' }}@if($resource->classSession) · {{ $resource->classSession->title }}@endif · {{ ['pdf' => 'جزوه PDF', 'book' => 'کتاب', 'other' => 'سایر منابع'][$resource->kind] ?? 'منبع آموزشی' }}</p>
            <b>{{ $resource->is_free ? 'رایگان' : number_format((int) $resource->price).' تومان' }}</b>
            @if($resource->file_path && ($resource->downloadable || $resource->kind === 'pdf') && ($resource->is_free || $purchase?->status === 'paid'))
                <a class="b s" href="{{ route('guardian.resources.download', ['resource' => $resource, 'child' => $kid?->id]) }}">مشاهده / دریافت منبع</a>
            @elseif($resource->file_path && !$resource->downloadable && $resource->kind !== 'pdf')
                <span class="tag">دریافت این منبع فعال نیست</span>
            @elseif(!$resource->file_path)
                <span class="tag">فایل هنوز بارگذاری نشده است</span>
            @elseif($purchase?->status === 'pending_review')
                <span class="tag">رسید خرید در انتظار تأیید آموزشگاه</span>
            @else
                <form method="POST" action="{{ route('guardian.resources.purchase', ['resource' => $resource, 'child' => $kid?->id]) }}">
                    @csrf
                    <label>شماره پیگیری واریز</label>
                    <input name="provider_reference" value="{{ old('provider_reference') }}" maxlength="190" required>
                    <label>توضیح (اختیاری)</label>
                    <input name="note" value="{{ old('note') }}" maxlength="1000">
                    <button class="b s" type="submit">ثبت درخواست خرید</button>
                </form>
            @endif
        </article>
    @empty
        <x-portal.empty-state icon="file" title="منبعی برای این فرزند منتشر نشده است." description="به‌محض انتشار منبع جدید، در همین بخش نمایش داده می‌شود."/>
    @endforelse
</div>
<div class="parent-pagination">{{ $resources->links() }}</div>
@endsection
