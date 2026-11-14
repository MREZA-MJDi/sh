@extends('layouts.panel')

@section('page-title', 'ارسال پیام')
@section('page-description', 'ارسال پیام مستقیم به دانش‌آموز یا ولیِ دانش‌آموزان کلاس‌های تحت مسئولیت شما')

@section('content')
    <section class="panel-card" data-tone="talk">
        <div class="panel-card-title"><div><h3>پیام جدید</h3><p>فقط دانش‌آموزان کلاس‌های فعال شما و اولیای متصل به همان دانش‌آموز قابل انتخاب هستند.</p></div></div>
        @if ($classrooms->isEmpty())
            <div class="panel-empty">کلاس فعالی برای ارسال پیام در دسترس نیست.</div>
        @else
            <form method="POST" action="{{ route('teacher.messages.store') }}" class="panel-form-grid">
                @csrf
                <label class="full">گیرنده
                    <select name="target" required>
                        <option value="">انتخاب دانش‌آموز یا ولی</option>
                        @foreach ($classrooms as $classroom)
                            @foreach ($classroom->students as $student)
                                <optgroup label="{{ $classroom->name }} · {{ $student->name }}">
                                    <option value="student:{{ $classroom->id }}:{{ $student->id }}" @selected(old('target') === "student:{$classroom->id}:{$student->id}")>دانش‌آموز: {{ $student->name }}</option>
                                    @foreach ($student->guardians as $guardian)
                                        <option value="parent:{{ $classroom->id }}:{{ $student->id }}:{{ $guardian->id }}" @selected(old('target') === "parent:{$classroom->id}:{$student->id}:{$guardian->id}")>ولی: {{ $guardian->name }} ({{ $student->name }})</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        @endforeach
                    </select>
                </label>
                <label class="full">موضوع
                    <input name="subject" value="{{ old('subject') }}" maxlength="190" required>
                </label>
                <label class="full">متن پیام
                    <textarea name="body" rows="6" maxlength="30000" data-rich-text required>{{ old('body') }}</textarea>
                </label>
                <div class="full"><button class="panel-btn" type="submit">ارسال پیام</button><a class="panel-btn panel-btn-secondary" href="{{ route('admin.messages.index') }}">بازگشت به کارتابل</a></div>
            </form>
        @endif
    </section>
@endsection