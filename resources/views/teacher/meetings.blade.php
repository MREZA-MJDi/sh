@extends('teacher.layouts.app')

@section('title', 'جلسات با اولیا')

@section('content')
<h2>جلسات با اولیا</h2>
<div class="g g2">
    <section class="c"><h3>درخواست‌های جدید</h3>
        @forelse (($meetingRequests ?? []) as $r)
            <div class="row"><div>{{ data_get($r, 'parent') }}<small>{{ data_get($r, 'time') }}</small></div>
                <form method="POST" action="{{ url('teacher/meetings/'.data_get($r, 'id').'/accept') }}" class="inline">@csrf<button class="b" type="submit">تأیید</button></form>
                <form method="POST" action="{{ url('teacher/meetings/'.data_get($r, 'id').'/propose') }}" class="inline">@csrf<button class="b s" type="submit">زمان دیگر</button></form>
            </div>
        @empty
            <p>درخواستی نیست. ✅</p>
        @endforelse
    </section>

    <section class="c"><h3>جلسه‌های تأییدشده</h3>
        @forelse (($meetings ?? []) as $m)
            <div class="row"><div>{{ data_get($m, 'parent') }}<small>{{ data_get($m, 'time') }}</small></div><i class="bd gn">تأیید</i></div>
        @empty
            <p>جلسه‌ای ثبت نشده است.</p>
        @endforelse
    </section>
</div>
@endsection
