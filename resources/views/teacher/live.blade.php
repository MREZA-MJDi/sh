@extends('teacher.layouts.app')

@section('title', 'کلاس آنلاین')

@section('content')
@php
    $pv = ['skyroom' => 'اسکای‌روم', 'bbb' => 'BigBlueButton', 'link' => 'لینک دستی'];
    $current = $provider ?? 'skyroom';
@endphp
<h2>کلاس آنلاین</h2>
<div class="g">
    @if (!empty($liveClass))
    <div class="live">
        <div>
            <h3><span class="pulse"></span>{{ data_get($liveClass, 'title') }}</h3>
            <p>{{ $pv[$current] ?? '' }} · شروع {{ data_get($liveClass, 'time') }} · {{ data_get($liveClass, 'waiting_count', 0) }} نفر در انتظار</p>
        </div>
        <a class="b" href="{{ data_get($liveClass, 'join_url', url('teacher/live/join')) }}" target="_blank" rel="noopener">ورود به کلاس</a>
    </div>
    @endif

    <div class="g g2">
        <section class="c"><h3>سرویس کلاس</h3>
            <form method="POST" action="{{ url('teacher/live/provider') }}" data-autosubmit>
                @csrf
                <div class="chips">
                    @foreach ($pv as $key => $text)
                        <label class="chip"><input type="radio" name="provider" value="{{ $key }}" data-label="{{ $text }}" @checked($current === $key)>{{ $text }}</label>
                    @endforeach
                </div>
            </form>
            <div class="frame"><div>پخش زنده <span data-provider-label>{{ $pv[$current] ?? '' }}</span><br><small>اینجا اتاق کلاس لود می‌شود</small></div></div>
        </section>

        <section class="c"><h3>روش اتصال</h3>
            <div class="row"><div>۱. هر کلاس یک اتاق دارد<small>شناسه اتاق در بخش زمان‌بندی ثبت می‌شود</small></div></div>
            <div class="row"><div>۲. لینک ورود اختصاصی<small>با کلیک روی ورود، سرور برای همان کاربر لینک می‌سازد</small></div></div>
            <div class="row"><div>۳. بازه زمانی<small>دکمه ورود فقط در ساعت کلاس فعال است</small></div></div>
        </section>

        <section class="c"><h3>زمان‌بندی کلاس‌ها و لینک‌ها</h3>
            @forelse (($links ?? []) as $l)
                <div class="row"><div>{{ data_get($l, 'title') }} <i class="bd bl">{{ $pv[data_get($l, 'provider')] ?? data_get($l, 'provider') }}</i><small>{{ data_get($l, 'schedule') }} · {{ data_get($l, 'room') }}</small></div>
                    @include('teacher.partials.crud-actions', ['entity' => 'links', 'item' => $l])
                </div>
            @empty
                <p>موردی ثبت نشده است.</p>
            @endforelse
            <br><button type="button" class="b" data-modal="links" data-action="{{ url('teacher/links') }}" data-method="POST">+ کلاس جدید</button>
        </section>
    </div>
</div>
@endsection
