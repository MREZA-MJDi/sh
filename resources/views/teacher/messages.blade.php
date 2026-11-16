@extends('teacher.layouts.app')

@section('title', 'پیام‌های اولیا')

@section('content')
<h2>پیام‌های اولیا</h2>
<div class="chat">
    <section class="c"><h3>گفت‌وگوها</h3>
        @forelse (($conversations ?? []) as $c)
            <a class="row" href="?c={{ data_get($c, 'id') }}"><div>{{ data_get($c, 'name') }}<small>{{ data_get($c, 'last') }}</small></div>
                @if ((int) data_get($c, 'unread', 0) > 0)<i class="bd {{ data_get($c, 'complaint') ? 'rd' : 'pk' }}">{{ data_get($c, 'unread') }}</i>@endif
            </a>
        @empty
            <p>گفت‌وگویی نیست.</p>
        @endforelse
    </section>

    <section class="c"><h3>{{ $conversationTitle ?? '' }}</h3>
        <div class="msgs">
            @foreach (($messages ?? []) as $m)
                <div class="m {{ data_get($m, 'mine') ? 'me2' : '' }}">{{ data_get($m, 'text') }}</div>
            @endforeach
        </div>
        <form class="send" method="POST" action="{{ url('teacher/messages/'.($conversationId ?? '').'/send') }}">
            @csrf
            <input name="message" placeholder="پیام خود را بنویسید…" autocomplete="off" required>
            <button class="b" type="submit">ارسال پیام</button>
        </form>
    </section>
</div>
@endsection
