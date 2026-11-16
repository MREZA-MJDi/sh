@extends('teacher.layouts.app')

@section('title', 'تکالیف')

@section('content')
<h2>تکالیف</h2>
<div class="g">
    <section class="c"><h3>تکلیف‌های ثبت‌شده</h3>
        @forelse (($homework ?? []) as $h)
            @php $total = max((int) data_get($h, 'total', 0), 1); $sent = (int) data_get($h, 'submitted', 0); @endphp
            <div class="row"><div>{{ data_get($h, 'title') }}<small>مهلت: {{ data_get($h, 'due') }} · {{ data_get($h, 'type') }}</small></div>
                <div class="pr"><u style="width:{{ round($sent / $total * 100) }}%"></u></div>
                <a class="b s" href="{{ url('teacher/homework/'.data_get($h, 'id')) }}">بررسی</a>
                @include('teacher.partials.crud-actions', ['entity' => 'homework', 'item' => $h])
            </div>
        @empty
            <p>تکلیفی ثبت نشده است.</p>
        @endforelse
        <br><button type="button" class="b" data-modal="homework" data-action="{{ url('teacher/homework') }}" data-method="POST">+ تکلیف جدید</button>
    </section>

    <section class="c"><h3>نمره‌دهی سریع</h3>
        <form method="POST" action="{{ url('teacher/homework/grades') }}">
            @csrf
            @forelse (($gradeStudents ?? []) as $s)
                <div class="row"><div>{{ data_get($s, 'name') }}<small>{{ data_get($s, 'homework') }}</small></div>
                    <input class="grade" type="number" min="0" max="20" step="0.25" name="grades[{{ data_get($s, 'id') }}]" value="{{ data_get($s, 'grade') }}">
                </div>
            @empty
                <p>موردی برای نمره‌دهی نیست.</p>
            @endforelse
            <br><button class="b" type="submit">ثبت نمره‌ها</button>
        </form>
    </section>
</div>
@endsection
