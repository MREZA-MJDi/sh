@extends('layouts.student')

@section('title', 'پروفایل من')

@section('content')
    <section class="card student-profile-card" aria-labelledby="student-profile-title">
        <div class="chead">
            <div>
                <span class="student-section-eyebrow">حساب کاربری</span>
                <h2 id="student-profile-title">اطلاعات پروفایل</h2>
            </div>
            <span class="pill">حساب دانش‌آموزی</span>
        </div>
        <p class="mu">نام و شماره تماس خود را به‌روز نگه دار. ایمیل ورود و پایه آموزشی برای جلوگیری از تغییر ناخواسته در این صفحه فقط‌خواندنی هستند.</p>

        <form method="POST" action="{{ route('student.profile.update') }}" class="student-profile-form">
            @csrf
            @method('PUT')
            <div class="student-profile-form__grid">
                <div class="student-profile-field">
                    <label for="profile-name">نام و نام خانوادگی</label>
                    <input id="profile-name" name="name" value="{{ old('name', $user->name) }}" maxlength="120" required autocomplete="name">
                    @error('name')<small role="alert">{{ $message }}</small>@enderror
                </div>
                <div class="student-profile-field">
                    <label for="profile-phone">شماره موبایل</label>
                    <input id="profile-phone" name="phone" value="{{ old('phone', $user->phone) }}" maxlength="30" inputmode="tel" dir="ltr" autocomplete="tel">
                    @error('phone')<small role="alert">{{ $message }}</small>@enderror
                </div>
                <div class="student-profile-field">
                    <label for="profile-email">ایمیل ورود</label>
                    <input id="profile-email" value="{{ $user->email }}" dir="ltr" disabled>
                </div>
                <div class="student-profile-field">
                    <label for="profile-grade">پایه تحصیلی</label>
                    <input id="profile-grade" value="{{ $user->grade ?: 'ثبت نشده' }}" disabled>
                </div>
            </div>
            <div class="student-profile-form__actions">
                <button class="btn" type="submit">ذخیره تغییرات</button>
                <a class="btn g" href="{{ route('student.dashboard') }}">بازگشت به داشبورد</a>
            </div>
        </form>
    </section>
@endsection
