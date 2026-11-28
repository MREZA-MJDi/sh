@extends('student.layouts.app')

@section('title', 'پیام جدید')

@section('content')
<section class="page student-message-page">
    <x-portal.page-head icon="message" tone="communication" title="ارسال پیام جدید" :back="route('student.messages.index')" back-label="بازگشت به پیام‌ها"/>

    <div class="card student-message-card">
        

        @if(!empty($supportCourse))
            <p class="student-message-empty">درخواست پشتیبانی دوره «{{ $supportCourse->title }}». اگر پرداخت انجام شده، شمارهٔ پیگیری را بنویسید. پاسخ در کارتابل پیام‌های شما نمایش داده می‌شود.</p>
        @endif
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
                                <option value="{{ $recipient->id }}" @selected((string) old('recipient_id', $supportRecipientId ?? null) === (string) $recipient->id)>
                                    {{ $recipient->name }}
                                </option>
                            @endforeach
                        </select>
                        <small>فقط مدرس‌های کلاس‌های فعال شما و مدیران آموزشگاه نمایش داده می‌شوند.</small>
                        @error('recipient_id')<small role="alert">{{ $message }}</small>@enderror
                    </div>

                    <div class="student-message-field">
                        <label for="subject">موضوع پیام</label>
                        <input id="subject" name="subject" type="text" value="{{ old('subject', !empty($supportCourse) ? 'مشکل پرداخت یا پخش دوره · '.\Illuminate\Support\Str::limit($supportCourse->title, 140) : '') }}"
                            minlength="3" maxlength="190" placeholder="مثلاً پرسش درباره تکلیف جلسه قبل" required>
                        <small>موضوع کوتاه و روشن انتخاب کنید.</small>
                        @error('subject')<small role="alert">{{ $message }}</small>@enderror
                    </div>

                    <div class="student-message-field is-wide">
                        <label for="body">متن پیام</label>
                        <textarea id="body" name="body" rows="7" minlength="5" maxlength="30000"
                            placeholder="متن پیام خود را اینجا بنویسید..." required>{{ old('body', !empty($supportCourse) ? route('public.courses.show', $supportCourse->slug)."\n\nشرح مشکل و شمارهٔ پیگیری (در صورت وجود): " : '') }}</textarea>
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
