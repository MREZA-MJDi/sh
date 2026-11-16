@extends('parent.layouts.app')

@section('title', 'پیام به مدرس')

@section('content')
<h2>پیام به مدرس</h2>
<section class="c"><h3>{{ $teacherTitle ?? '' }}</h3>
    <form method="POST" action="{{ url('parent/messages/send') }}">
        @csrf
        <input type="hidden" name="teacher_id" value="{{ $teacherId ?? '' }}">
        <div class="chips">
            @foreach (['سوال', 'انتقاد', 'پیشنهاد'] as $i => $cat)
                <label class="chip"><input type="radio" name="category" value="{{ $cat }}" @checked($i === 0)>{{ $cat }}</label>
            @endforeach
        </div>
        <div class="msgs">
            @foreach (($messages ?? []) as $m)
                <div class="m {{ data_get($m, 'mine') ? 'me2' : '' }}">{{ data_get($m, 'text') }}</div>
            @endforeach
        </div>
        <div class="send">
            <input name="message" placeholder="پیام خود را بنویسید…" autocomplete="off" required>
            <button class="b" type="submit">ارسال پیام</button>
        </div>
    </form>
</section>
@endsection
