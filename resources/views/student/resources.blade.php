@extends('student.layouts.app')
@section('title', 'جزوه‌ها و منابع')
@section('content')
<section class="page" id="resources">
    <div class="card">
        <div class="chead">
            <h2>جزوه‌ها، کتاب‌ها و محصولات آموزشی</h2>
            <span class="pill">{{ number_format($resources->total()) }} منبع</span>
        </div>
        <div class="g3">
            @forelse(($resources ?? collect()) as $resource)
                @php $purchase = $resource->purchases->first(); @endphp
                <article class="card cc">
                    <div class="th"><svg class="i"><use href="#i-file"/></svg></div>
                    <h3>{{ $resource->title }}</h3>
                    <p>{{ $resource->course?->title ?? $resource->classroom?->name ?? 'منبع عمومی' }}@if($resource->classSession) · {{ $resource->classSession->title }}@endif · {{ ['pdf'=>'جزوه PDF','book'=>'کتاب','other'=>'منبع آموزشی'][$resource->kind] ?? 'فایل آموزشی' }}</p>
                    <p><b>{{ $resource->is_free ? 'رایگان' : number_format((int) $resource->price).' تومان' }}</b></p>

                    @if($resource->file_path && ($resource->downloadable || $resource->kind === 'pdf') && ($resource->is_free || $purchase?->status === 'paid'))
                        <a class="btn s" href="{{ route('student.resources.download', $resource) }}">مشاهده / دریافت منبع</a>
                    @elseif($resource->file_path && !$resource->downloadable && $resource->kind !== 'pdf')
                        <span class="tag">دریافت این منبع فعال نیست</span>
                    @elseif(!$resource->file_path)
                        <span class="tag">فایل هنوز بارگذاری نشده است</span>
                    @elseif($purchase?->status === 'pending_review')
                        <span class="tag">رسید خرید در انتظار تأیید آموزشگاه</span>
                    @else
                        <form method="POST" action="{{ route('student.resources.purchase', $resource) }}" class="assignment-form">
                            @csrf
                            <label>شماره پیگیری واریز</label>
                            <input class="fld" name="provider_reference" maxlength="190" required placeholder="شماره پیگیری رسید بانکی">
                            <label>توضیح (اختیاری)</label>
                            <input class="fld" name="note" maxlength="1000" placeholder="توضیح پرداخت">
                            <button class="btn s" type="submit">ثبت درخواست خرید</button>
                        </form>
                    @endif
                </article>
            @empty
                <x-portal.empty-state icon="file" title="منبع آموزشی منتشر نشده است." description="جزوه‌ها، کتاب‌ها و محصولات مجاز دوره‌های شما پس از انتشار در این صفحه قرار می‌گیرند."/>
            @endforelse
        </div>
        {{ $resources->links() }}
    </div>
</section>
@endsection
