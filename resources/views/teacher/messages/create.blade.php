@extends('layouts.panel')

@section('page-title', 'ارسال پیام')
@section('page-description', 'ارسال پیام به مدیریت آکادمی، دانش‌آموز یا ولیِ دانش‌آموزان کلاس‌های شما')

@section('content')
    <section class="panel-card" data-tone="talk">
        <div class="panel-card-title"><div><h3>پیام جدید</h3><p>می‌توانید پیام را مستقیم به مدیریت آکادمی یا به دانش‌آموزان و اولیای کلاس‌های فعال خود ارسال کنید.</p></div></div>
        @if ($classrooms->isEmpty())
            <div class="panel-empty">کلاس فعالی برای پیام به دانش‌آموز یا ولی در دسترس نیست؛ می‌توانید به مدیریت آکادمی پیام بدهید.</div>
        @endif
            <form method="POST" action="{{ route('teacher.messages.store') }}" class="panel-form-grid">
                @csrf
                <label class="full">گیرنده
                    <select name="target" required>
                        <option value="">انتخاب مخاطب</option>
                        <option value="admin" @selected(old('target') === 'admin')>مدیریت آکادمی (کارتابل ادمین)</option>
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
    </section>
@endsection