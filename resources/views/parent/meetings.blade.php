@extends('parent.layouts.app')

@section('title', 'جلسات مشاوره')

@section('content')
<h2>جلسات مشاوره</h2>
<div class="g g2">
    <section class="c"><h3>درخواست جلسه جدید</h3>
        <form method="POST" action="{{ url('parent/meetings') }}" data-require-choice="ابتدا یک زمان انتخاب کنید">
            @csrf
            <div class="chips">
                @foreach (($slots ?? []) as $s)
                    <label class="chip"><input type="radio" name="slot" value="{{ data_get($s, 'value', $s) }}">{{ data_get($s, 'label', $s) }}</label>
                @endforeach
            </div>
            <button class="b" type="submit">ارسال درخواست</button>
        </form>
    </section>

    <section class="c"><h3>جلسه‌های من</h3>
        {{-- status: confirmed | done | pending --}}
        @forelse (($meetings ?? []) as $m)
            @php $st = data_get($m, 'status'); @endphp
            <div class="row"><div>{{ data_get($m, 'teacher') }}<small>{{ data_get($m, 'time') }}</small></div>
                @if ($st === 'confirmed')<i class="bd gn">تأیید</i>
                @elseif ($st === 'done')<i class="bd bl">انجام شد</i>
                @else<i class="bd am">منتظر</i>@endif
            </div>
        @empty
            <p>جلسه‌ای ثبت نشده است.</p>
        @endforelse
    </section>
</div>
@endsection
