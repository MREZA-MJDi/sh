@extends('admin.layouts.app')

@php
    $labels = ['student' => 'دانش‌آموز', 'teacher' => 'مدرس', 'parent' => 'ولی'];
    $storeRoute = ['student' => 'admin.students.store', 'teacher' => 'admin.teachers.store', 'parent' => 'admin.parents.store'][$role];
    $teacherView = auth()->user()->hasRole('teacher') && ! auth()->user()->hasRole('admin');
@endphp

@section('page-title', $labels[$role].'‌ها')
@section('page-description', 'فهرست حساب‌ها و اطلاعات آموزشی مرتبط')

@section('content')
    <div class="panel-page-head">
        <div><h2>{{ $labels[$role] }}‌ها</h2><p>{{ number_format($users->total()) }} حساب در این فهرست</p></div>
        @can('users.manage')
            <button class="panel-btn" type="button" data-admin-drawer-open="user-create" data-drawer-title="ثبت {{ $labels[$role] }}">＋ ثبت {{ $labels[$role] }}</button>
        @endcan
    </div>

    <section class="panel-card">
        <div class="panel-card-title">
            <div><h3>فهرست {{ $labels[$role] }}‌ها</h3><p>برای حفظ سوابق، حساب‌ها از این صفحه حذف نمی‌شوند.</p></div>
            <form method="GET" action="{{ url()->current() }}" class="panel-inline-form">
                <input class="panel-input" name="q" value="{{ request('q') }}" placeholder="جستجو با نام یا راه ارتباطی">
                <button class="panel-btn panel-btn-secondary panel-btn-sm" type="submit">جستجو</button>
            </form>
        </div>
        <div class="panel-table-wrap">
            <table class="panel-table" data-searchable-table>
                <thead><tr><th>کاربر</th><th>راه ارتباطی</th><th>اطلاعات</th><th>وضعیت</th>@can('users.manage')<th>عملیات</th>@endcan</tr></thead>
                <tbody>
                @forelse ($users as $user)
                    <tr>
                        <td><strong>{{ $user->name }}</strong><div class="panel-muted">شناسه: {{ $user->id }}</div></td>
                        <td><div>{{ $user->email }}</div><div class="panel-muted" dir="ltr">{{ $user->phone ?: '—' }}</div></td>
                        <td>
                            @if ($role === 'student')
                                {{ $user->grade ?: '—' }}
                                @if ($user->external_student_id)<div class="panel-muted" dir="ltr">Quiz24: {{ $user->external_student_id }}</div>@endif
                                <div class="panel-muted">{{ $user->enrolledClasses()->count() }} کلاس</div>
                            @elseif ($role === 'teacher')
                                {{ $user->subject ?: '—' }}
                            @else
                                {{ $user->children()->count() }} فرزند متصل
                            @endif
                        </td>
                        <td><x-admin.badge :variant="$user->is_active ? 'success' : 'danger'">{{ $user->is_active ? 'فعال' : 'غیرفعال' }}</x-admin.badge></td>
                        @can('users.manage')
                            <td>
                                <form method="POST" action="{{ route('admin.users.status', $user) }}" class="panel-inline-form">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="is_active" value="{{ $user->is_active ? 0 : 1 }}">
                                    <button class="panel-btn panel-btn-sm {{ $user->is_active ? 'panel-btn-danger' : 'panel-btn-success' }}" type="submit">
                                        {{ $user->is_active ? 'غیرفعال‌سازی' : 'فعال‌سازی' }}
                                    </button>
                                </form>
                            </td>
                        @endcan
                    </tr>
                @empty
                    <tr><td colspan="5" class="panel-empty">کاربری برای نمایش وجود ندارد.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="panel-pagination"><span>نمایش {{ $users->count() }} از {{ $users->total() }} حساب</span>{{ $users->links() }}</div>
    </section>

    @can('users.manage')
        <x-admin.drawer-form id="user-create" title="ثبت کاربر" :action="route($storeRoute)" submit-label="ثبت {{ $labels[$role] }}">
            <div class="panel-field"><label>نام و نام خانوادگی</label><input class="panel-input" name="name" value="{{ old('name') }}" required maxlength="160"></div>
            <div class="panel-field"><label>ایمیل ورود</label><input class="panel-input" type="email" name="email" value="{{ old('email') }}" required maxlength="190" dir="ltr"></div>
            <div class="panel-field"><label>شماره تماس</label><input class="panel-input" name="phone" value="{{ old('phone') }}" maxlength="30" dir="ltr"></div>
            <div class="panel-field"><label>گذرواژه اولیه</label><input class="panel-input" type="password" name="password" minlength="12" required autocomplete="new-password"></div>
            <div class="panel-field"><label>تکرار گذرواژه</label><input class="panel-input" type="password" name="password_confirmation" minlength="12" required autocomplete="new-password"></div>
            @if ($role === 'student')
                <div class="panel-field"><label>پایه تحصیلی</label><select class="panel-select" name="grade" required><option value="">انتخاب پایه</option>@foreach (['دهم','یازدهم','دوازدهم','فارغ‌التحصیل'] as $grade)<option value="{{ $grade }}">{{ $grade }}</option>@endforeach</select></div>
                <div class="panel-field"><label>شناسه دانش‌آموز در Quiz24 (اختیاری)</label><input class="panel-input" name="external_student_id" maxlength="120" dir="ltr"></div>
                <div class="panel-field"><label>عضویت در کلاس‌ها</label><select class="panel-select" name="classroom_ids[]" multiple size="6">@foreach ($classrooms as $classroom)<option value="{{ $classroom->id }}">{{ $classroom->name }}</option>@endforeach</select><span class="panel-muted">برای انتخاب چند مورد، Ctrl یا Command را نگه دارید.</span></div>
            @elseif ($role === 'teacher')
                <div class="panel-field"><label>درس تخصصی</label><input class="panel-input" name="subject" maxlength="100" required></div>
            @elseif ($role === 'parent')
                <div class="panel-field"><label>اتصال به دانش‌آموزان</label><select class="panel-select" name="child_ids[]" multiple size="6">@foreach ($students as $student)<option value="{{ $student->id }}">{{ $student->name }}</option>@endforeach</select></div>
            @endif
        </x-admin.drawer-form>
    @endcan
@endsection
