{{-- نیازمند: $course, $state --}}
@php
    use App\Services\Learning\CourseAccess as A;
    $s = $state['state'];
    $price = number_format((int) $course->price);
@endphp

<aside class="access-box" style="border:1px solid #e2e8f0;border-radius:16px;padding:16px">
    @if ($errors->has('payment')) <p style="color:#b91c1c">{{ $errors->first('payment') }}</p> @endif
    @if (session('status')) <p style="color:#15803d">{{ session('status') }}</p> @endif

    @switch($s)
        @case(A::GUEST)
            <p style="font-size:1.3em;font-weight:700">{{ $course->isFree() ? 'رایگان' : $price . ' تومان' }}</p>
            <a class="btn btn-primary" href="{{ route('login') }}">{{ $course->isFree() ? 'ورود و ثبت‌نام رایگان' : 'ورود و خرید دوره' }}</a>
            @break

        @case(A::NOT_ENROLLED)
            <p style="font-size:1.3em;font-weight:700">{{ $course->isFree() ? 'رایگان' : $price . ' تومان' }}</p>
            <form method="POST" action="{{ route('courses.enroll', $course->slug) }}">
                @csrf
                <button class="btn btn-primary" type="submit">{{ $course->isFree() ? 'ثبت‌نام رایگان' : 'خرید دوره' }}</button>
            </form>
            @break

        @case(A::PENDING_PAYMENT)
            <p>پرداخت شما هنوز تکمیل نشده است. دسترسی بعد از تأیید پرداخت فعال می‌شود.</p>
            <form method="POST" action="{{ route('courses.enroll', $course->slug) }}">
                @csrf
                <button class="btn btn-primary" type="submit">ادامه پرداخت ({{ $price }} تومان)</button>
            </form>
            @break

        @case(A::LICENSE_PREPARING)
        @case(A::LICENSE_WAITING)
            <p><strong>ثبت‌نام شما فعال شد.</strong> لایسنس پخش در حال آماده‌سازی است؛ این صفحه خودکار به‌روز می‌شود.</p>
            <p style="color:#64748b">اگر بیش از چند دقیقه طول کشید با پشتیبانی تماس بگیرید.</p>
            <script>
                (function () {
                    var tries = 0, timer = setInterval(function () {
                        if (++tries > 40) { clearInterval(timer); return; }
                        fetch(@json(route('courses.access-status', $course->slug)), {headers: {'Accept': 'application/json'}})
                            .then(function (r) { return r.json(); })
                            .then(function (d) { if (d.state === 'active') { clearInterval(timer); location.reload(); } })
                            .catch(function () {});
                    }, 6000);
                })();
            </script>
            @break

        @case(A::LICENSE_FAILED)
            <p style="color:#b91c1c">صدور لایسنس با مشکل مواجه شد. پرداخت/ثبت‌نام شما محفوظ است؛ لطفاً با پشتیبانی تماس بگیرید.</p>
            @break

        @case(A::REVOKED)
            <p style="color:#b91c1c">دسترسی شما به این دوره لغو شده است.</p>
            @break

        @case(A::ACTIVE)
            <p style="color:#15803d;font-weight:700">شما در این دوره ثبت‌نام کرده‌اید.</p>
            @if ($course->type === \App\Enums\CourseType::OnlineClass)
                @if ($errors->has('join')) <p style="color:#b91c1c">{{ $errors->first('join') }}</p> @endif
                <a class="btn btn-primary" href="{{ route('courses.join', $course->slug) }}">🔴 ورود به کلاس زنده</a>
            @elseif ($course->type === \App\Enums\CourseType::InPerson)
                <p>📍 {{ $course->location }}</p>
            @else
                @include('courses._license_box')
            @endif
            @break
    @endswitch
</aside>
