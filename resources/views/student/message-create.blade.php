@extends('student.layouts.app')

@section('title', 'پیام جدید')

@section('content')
<section class="page student-message-page">
    <header class="student-message-heading">
        <div>
            <h1>ارسال پیام جدید</h1>
            <p>برای ارتباط با مدرس یا مدیر آموزشگاه، فرم زیر را تکمیل کنید.</p>
        </div>
        <a class="btn s" href="{{ route('student.messages.index') }}">بازگشت به پیام‌ها</a>
    </header>

    <div class="card student-message-card">
        <div class="student-message-intro">
            <svg class="i" aria-hidden="true"><use href="#i-msg"/></svg>
            <span>پیام شما به‌صورت یک گفت‌وگوی مستقل ثبت می‌شود و پاسخ‌ها را از همین بخش دنبال می‌کنید.</span>
        </div>

        @if ($recipients->isEmpty())
            <div class="student-message-empty" role="status">
                <strong>در حال حاضر گیرنده‌ای برای ارسال پیام در دسترس نیست.</strong>
                <span>برای فعال‌شدن این امکان، باید مدرس کلاس یا مدیر فعال در حساب آموزشگاه وجود داشته باشد.</span>
            </div>
        @else
            <form class="student-message-form" method="POST" action="{{ route('student.messages.store') }}">
                @csrf
                <input type="hidden" name="student_id" value="{{ $student->id }}">

                <div class="student-message-fields">
                    <div class="student-message-field">
                        <label for="recipient_id">گیرنده پیام</label>
                        <select id="recipient_id" name="recipient_id" required>
                            <option value="">انتخاب مدرس یا مدیر</option>
                            @foreach ($recipients as $recipient)
                                <option value="{{ $recipient->id }}" @selected((string) old('recipient_id') === (string) $recipient->id)>
                                    {{ $recipient->name }}
                                </option>
                            @endforeach
                        </select>
                        <small>فقط مدرس‌های کلاس‌های فعال شما و مدیران آموزشگاه نمایش داده می‌شوند.</small>
                        @error('recipient_id')<small role="alert">{{ $message }}</small>@enderror
                    </div>

                    <div class="student-message-field">
                        <label for="subject">موضوع پیام</label>
                        <input id="subject" name="subject" type="text" value="{{ old('subject') }}"
                            minlength="3" maxlength="190" placeholder="مثلاً پرسش درباره تکلیف جلسه قبل" required>
                        <small>موضوع کوتاه و روشن انتخاب کنید.</small>
                        @error('subject')<small role="alert">{{ $message }}</small>@enderror
                    </div>

                    <div class="student-message-field is-wide">
                        <label for="body">متن پیام</label>
                        <textarea id="body" name="body" rows="7" minlength="5" maxlength="30000"
                            placeholder="متن پیام خود را اینجا بنویسید..." required>{{ old('body') }}</textarea>
                        <small>توضیحات خود را کامل و محترمانه بنویسید.</small>
                        @error('body')<small role="alert">{{ $message }}</small>@enderror
                    </div>
                </div>

                <div class="student-message-actions">
                    <small>پس از ارسال، پیام در فهرست گفت‌وگوهای شما قرار می‌گیرد.</small>
                    <div>
                        <a class="btn s" href="{{ route('student.messages.index') }}">انصراف</a>
                        <button class="btn" type="submit">ارسال پیام</button>
                    </div>
                </div>
            </form>
        @endif
    </div>
</section>
@endsection
