@extends('layouts.panel')

@php
    $labels = ['student' => 'دانش‌آموز', 'teacher' => 'مدرس', 'parent' => 'ولی'];
    $storeRoute = ['student' => 'admin.students.store', 'teacher' => 'admin.teachers.store', 'parent' => 'admin.parents.store'][$role];
@endphp

@section('page-title', $labels[$role].'‌ها')
@section('page-description', 'مدیریت حساب‌ها و ارتباط کاربران با کلاس‌ها و یکدیگر')

@section('content')
    <section class="panel-card mb-4">
        <div class="panel-card-title">
            <div><h3>ایجاد {{ $labels[$role] }} جدید</h3><p>حساب کاربری با نقش مشخص ایجاد می‌شود؛ گذرواژه حداقل ۱۲ کاراکتر است.</p></div>
            <span class="panel-badge">{{ number_format($users->total()) }} حساب</span>
        </div>

        <form method="POST" action="{{ route($storeRoute) }}">
            @csrf
            <div class="panel-form-grid panel-form-grid-3">
                <div class="panel-field">
                    <label for="name">نام و نام خانوادگی</label>
                    <input class="panel-input" id="name" name="name" value="{{ old('name') }}" required maxlength="160">
                </div>
                <div class="panel-field">
                    <label for="email">ایمیل ورود</label>
                    <input class="panel-input" id="email" name="email" type="email" value="{{ old('email') }}" required maxlength="190">
                </div>
                <div class="panel-field">
                    <label for="phone">شماره تماس</label>
                    <input class="panel-input" id="phone" name="phone" value="{{ old('phone') }}" maxlength="30" dir="ltr">
                </div>
                <div class="panel-field">
                    <label for="password">گذرواژه اولیه</label>
                    <input class="panel-input" id="password" name="password" type="password" minlength="12" required autocomplete="new-password">
                </div>
                <div class="panel-field">
                    <label for="password_confirmation">تکرار گذرواژه</label>
                    <input class="panel-input" id="password_confirmation" name="password_confirmation" type="password" minlength="12" required autocomplete="new-password">
                </div>

                @if ($role === 'student')
                    <div class="panel-field">
                        <label for="grade">پایه تحصیلی</label>
                        <select class="panel-select" id="grade" name="grade" required>
                            <option value="">انتخاب پایه</option>
                            @foreach (['دهم', 'یازدهم', 'دوازدهم', 'فارغ‌التحصیل'] as $grade)
                                <option value="{{ $grade }}" @selected(old('grade') === $grade)>{{ $grade }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="panel-field">
                        <label for="external_student_id">شناسه پایدار دانش‌آموز در Quiz24 (در صورت وجود)</label>
                        <input class="panel-input" id="external_student_id" name="external_student_id" value="{{ old('external_student_id') }}" maxlength="120" dir="ltr" autocomplete="off">
                        <p class="panel-muted mt-1">پس از تحویل قرارداد Quiz24 برای تطبیق نتایج و ورود یکپارچه استفاده می‌شود.</p>
                    </div>
                @elseif ($role === 'teacher')
                    <div class="panel-field">
                        <label for="subject">درس تخصصی</label>
                        <input class="panel-input" id="subject" name="subject" value="{{ old('subject') }}" maxlength="100" required>
                    </div>
                @endif
            </div>

            @if ($role === 'student')
                <div class="panel-field mt-3">
                    <label for="classroom_ids">عضویت در کلاس‌ها</label>
                    <select class="panel-select" id="classroom_ids" name="classroom_ids[]" multiple size="4">
                        @foreach ($classrooms as $classroom)
                            <option value="{{ $classroom->id }}" @selected(in_array($classroom->id, old('classroom_ids', [])))>{{ $classroom->name }}</option>
                        @endforeach
                    </select>
                    <p class="panel-muted mt-1">برای انتخاب چند کلاس، کلید Ctrl یا Command را نگه دارید.</p>
                </div>
            @elseif ($role === 'parent')
                <div class="panel-field mt-3">
                    <label for="child_ids">اتصال به دانش‌آموزان</label>
                    <select class="panel-select" id="child_ids" name="child_ids[]" multiple size="4">
                        @foreach ($students as $student)
                            <option value="{{ $student->id }}" @selected(in_array($student->id, old('child_ids', [])))>{{ $student->name }}</option>
                        @endforeach
                    </select>
                    <p class="panel-muted mt-1">فقط دانش‌آموزانی که انتخاب شوند در پنل ولی قابل مشاهده خواهند بود.</p>
                </div>
            @endif

            <div class="panel-form-actions">
                <button class="panel-btn" type="submit">ثبت {{ $labels[$role] }}</button>
            </div>
        </form>
    </section>

    <section class="panel-card">
        <div class="panel-card-title">
            <div><h3>فهرست {{ $labels[$role] }}‌ها</h3><p>برای حفظ سوابق آموزشی، حذف حساب از این صفحه انجام نمی‌شود.</p></div>
            <form method="GET" action="{{ url()->current() }}" class="panel-inline-form">
                <input class="panel-input" name="q" value="{{ request('q') }}" placeholder="نام، ایمیل یا موبایل">
                <button class="panel-btn panel-btn-secondary panel-btn-sm" type="submit">جستجو</button>
            </form>
        </div>

        <div class="panel-table-wrap">
            <table class="panel-table" data-searchable-table>
                <thead><tr><th>کاربر</th><th>راه ارتباطی</th><th>اطلاعات آموزشی</th><th>وضعیت</th><th>عملیات</th></tr></thead>
                <tbody>
                @forelse ($users as $user)
                    <tr>
                        <td><strong>{{ $user->name }}</strong><div class="panel-muted">شناسه: {{ $user->id }}</div></td>
                        <td><div>{{ $user->email }}</div><div class="panel-muted" dir="ltr">{{ $user->phone ?: '—' }}</div></td>
                        <td>
                            {{ $role === 'student' ? ($user->grade ?: '—') : ($role === 'teacher' ? ($user->subject ?: '—') : $user->children()->count().' فرزند متصل') }}
                            @if ($role === 'student' && $user->external_student_id)
                                <div class="panel-muted" dir="ltr">Quiz24: {{ $user->external_student_id }}</div>
                            @endif
                        </td>
                        <td>
                            @if ($user->is_active)
                                <span class="panel-badge panel-badge-success">فعال</span>
                            @else
                                <span class="panel-badge panel-badge-danger">غیرفعال</span>
                            @endif
                        </td>
                        <td>
                            <form method="POST" action="{{ route('admin.users.status', $user) }}" class="panel-inline-form">
                                @csrf @method('PATCH')
                                <input type="hidden" name="is_active" value="{{ $user->is_active ? 0 : 1 }}">
                                <button class="panel-btn panel-btn-sm {{ $user->is_active ? 'panel-btn-danger' : 'panel-btn-success' }}" type="submit">
                                    {{ $user->is_active ? 'غیرفعال‌سازی' : 'فعال‌سازی' }}
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="panel-empty">کاربری برای نمایش وجود ندارد.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="panel-pagination"><span>نمایش {{ $users->count() }} از {{ $users->total() }} حساب</span>{{ $users->links() }}</div>
    </section>
@endsection
