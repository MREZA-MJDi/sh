@extends('teacher.layouts.app')
@section('title', 'تکالیف')
@section('content')
<h2>تکالیف</h2>
<div class="g g2">
    <section class="c"><h3>تکلیف‌های ثبت‌شده</h3>
        @forelse ($homework as $item)
            <div class="row"><div>{{ $item->title }}<small>{{ $item->classroom?->name }} · مهلت: {{ $item->due_at?->format('Y/m/d H:i') ?? 'بدون مهلت' }}</small></div>
                <div class="pr"><u style="width:{{ min(100, $item->submissions_count * 100 / max($item->classroom?->students_count ?? 1, 1)) }}%"></u></div>
                <i class="bd bl">{{ $item->submissions_count }} ارسال</i>
            </div>
        @empty
            <p>تکلیفی برای کلاس‌های تحت مدیریت شما ثبت نشده است.</p>
        @endforelse
        {{ $homework->links() }}
    </section>

    <section class="c"><h3>نمره‌دهی سریع</h3>
        @php $submissions = $submissions ?? collect(); @endphp
        @forelse ($submissions as $submission)
            <form method="POST" action="{{ route('teacher.homework.review', $submission) }}" class="row">
                @csrf @method('PATCH')
                <div>{{ $submission->student?->name }}<small>{{ $submission->homework?->title }}</small>
                    @foreach($submission->files as $file)
                        <a href="{{ route('admin.homework-submission-files.download', $file) }}">{{ $file->original_name }}</a>
                    @endforeach
                </div>
                <input class="grade" type="number" min="0" max="20" step="0.25" name="score" value="{{ $submission->score }}" placeholder="نمره">
                <input type="text" name="feedback" maxlength="30000" required placeholder="بازخورد">
                <button class="b s" type="submit">ثبت</button>
            </form>
        @empty
            <p>موردی برای نمره‌دهی در این صفحه نیست.</p>
        @endforelse
        {{ $submissions->links() }}
    </section>
</div>
@endsection
