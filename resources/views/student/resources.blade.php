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
                    <p>{{ $resource->course?->title ?? $resource->classroom?->name ?? 'منبع عمومی' }} · {{ $resource->kind }}</p>
                    <p><b>{{ $resource->is_free ? 'رایگان' : number_format((int) $resource->price).' تومان' }}</b></p>

                    @if($resource->file_path && ($resource->is_free || $purchase?->status === 'paid'))
                        <a class="btn s" href="{{ route('student.resources.download', $resource) }}">مشاهده / دریافت منبع</a>
                    @elseif(!$resource->file_path)
                        <span class="tag">فایل هنوز بارگذاری نشده است</span>
                    @elseif($purchase?->status === 'pending_review')
                        <span class="tag">رسید خرید در انتظار تأیید آموزشگاه</span>
                    @else
                        <form method="POST" action="{{ route('student.resources.purchase', $resource) }}" class="adm-stack">
                            @csrf
                            <label>شماره پیگیری واریز</label>
                            <input class="adm-input" name="provider_reference" maxlength="190" required placeholder="شماره پیگیری رسید بانکی">
                            <label>توضیح (اختیاری)</label>
                            <input class="adm-input" name="note" maxlength="1000" placeholder="توضیح پرداخت">
                            <button class="btn s" type="submit">ثبت درخواست خرید</button>
                        </form>
                    @endif
                </article>
            @empty
                <p>منبع منتشرشده‌ای برای دوره‌ها و کلاس‌های فعال شما وجود ندارد.</p>
            @endforelse
        </div>
        {{ $resources->links() }}
    </div>
</section>
@endsection
