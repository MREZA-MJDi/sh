@extends('admin.layouts.app')

@php
    $labels = ['student' => 'دانش‌آموز', 'teacher' => 'مدرس', 'parent' => 'ولی'];
    $storeRoute = ['student' => 'admin.students.store', 'teacher' => 'admin.teachers.store', 'parent' => 'admin.parents.store'][$role];
@endphp

@section('page-title', $labels[$role].'‌ها')
@section('page-description', 'مدیریت حساب‌ها و ارتباط آن‌ها با کلاس‌ها و دوره‌های آکادمی')

@section('content')
    <div class="panel-page-head">
        <div><h2>{{ $labels[$role] }}‌ها</h2><p>{{ number_format($users->total()) }} حساب ثبت‌شده</p></div>
        @can('users.manage')
            <button class="panel-btn" type="button" data-admin-drawer-open="user-create" data-drawer-title="{{ $role === 'teacher' ? 'ساخت مدرس' : 'ثبت '.$labels[$role] }}">
                ＋ {{ $role === 'teacher' ? 'ساخت مدرس' : 'ثبت '.$labels[$role] }}
            </button>
        @endcan
    </div>

    <section class="panel-card">
        <div class="panel-card-title">
            <div><h3>فهرست {{ $labels[$role] }}‌ها</h3><p>جستجو بر اساس نام، شماره تماس یا ایمیل</p></div>
            <form method="GET" action="{{ url()->current() }}" class="panel-inline-form">
                <input class="panel-input" name="q" value="{{ request('q') }}" placeholder="نام یا موبایل">
                <button class="panel-btn panel-btn-secondary panel-btn-sm" type="submit">جستجو</button>
            </form>
        </div>
        <div class="panel-table-wrap">
            <table class="panel-table" data-searchable-table>
                @if ($role === 'teacher')
                    <thead><tr><th>مدرس</th><th>درس</th><th>دوره‌های تخصیص‌یافته</th><th>دسترسی‌ها</th><th>وضعیت</th>@can('users.manage')<th>عملیات</th>@endcan</tr></thead>
                @else
                    <thead><tr><th>کاربر</th><th>راه ارتباطی</th><th>اطلاعات</th><th>وضعیت</th>@can('users.manage')<th>عملیات</th>@endcan</tr></thead>
                @endif
                <tbody>
                @forelse ($users as $user)
                    <tr>
                        @if ($role === 'teacher')
                            <td><strong>{{ $user->name }}</strong><div class="panel-muted" dir="ltr">{{ $user->phone ?: '—' }}</div></td>
                            <td>{{ $user->subject ?: '—' }}</td>
                            <td>
                                <div class="flex flex-wrap gap-1">
                                    @forelse ($user->assignedCourses as $course)
                                        <x-admin::badge>{{ $course->title }}</x-admin.badge>
                                    @empty
                                        <span class="panel-muted">دوره‌ای تخصیص داده نشده</span>
                                    @endforelse
                                </div>
                            </td>
                            <td>
                                @php($selectedGroups = $teacherSelectedGroups[$user->id] ?? [])
                                <div class="flex flex-wrap gap-1">
                                    @foreach ($teacherAccessGroups as $key => $group)
                                        @if ($selectedGroups[$key] ?? false)
                                            <x-admin::badge>{{ $group['label'] }}</x-admin.badge>
                                        @endif
                                    @endforeach
                                    @if (! collect($selectedGroups)->contains(true))
                                        <span class="panel-muted">فقط دسترسی پایه</span>
                                    @endif
                                </div>
                            </td>
                            <td><x-admin::badge :variant="$user->is_active ? 'success' : 'warning'">{{ $user->is_active ? 'فعال' : 'در انتظار تأیید' }}</x-admin.badge></td>
                            @can('users.manage')
                                <td><button class="panel-btn panel-btn-secondary panel-btn-sm" type="button" data-admin-drawer-open="teacher-edit-{{ $user->id }}" data-drawer-title="ویرایش دسترسی مدرس">ویرایش</button></td>
                            @endcan
                        @else
                            <td><strong>{{ $user->name }}</strong><div class="panel-muted">شناسه: {{ $user->id }}</div></td>
                            <td><div>{{ $user->email ?: '—' }}</div><div class="panel-muted" dir="ltr">{{ $user->phone ?: '—' }}</div></td>
                            <td>
                                @if ($role === 'student')
                                    {{ $user->grade ?: '—' }}
                                    @if ($user->external_student_id)<div class="panel-muted" dir="ltr">Quiz24: {{ $user->external_student_id }}</div>@endif
                                    <div class="panel-muted">{{ $user->enrolledClasses()->count() }} کلاس</div>
                                @else
                                    {{ $user->children()->count() }} فرزند متصل
                                @endif
                            </td>
                            <td><x-admin::badge :variant="$user->is_active ? 'success' : 'danger'">{{ $user->is_active ? 'فعال' : 'غیرفعال' }}</x-admin.badge></td>
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
                        @endif
                    </tr>

                    @if ($role === 'teacher')
                        @can('users.manage')
                            @php($selectedGroups = $teacherSelectedGroups[$user->id] ?? [])
                            <x-admin::drawer-form :id="'teacher-edit-'.$user->id" title="ویرایش دسترسی مدرس" :action="route('admin.teachers.update', $user)" method="POST" submit-label="ذخیره دسترسی‌ها">
                                @method('PATCH')
                                <div class="panel-field"><label>نام و نام خانوادگی</label><input class="panel-input" name="name" value="{{ $user->name }}" required maxlength="160"></div>
                                <div class="panel-field"><label>موبایل (نام کاربری)</label><input class="panel-input" name="phone" value="{{ $user->phone }}" required maxlength="30" dir="ltr" inputmode="tel"></div>
                                <div class="panel-field"><label>ایمیل (اختیاری)</label><input class="panel-input" type="email" name="email" value="{{ $user->email }}" maxlength="190" dir="ltr"></div>
                                <div class="panel-field"><label>درس</label><input class="panel-input" name="subject" value="{{ $user->subject }}" maxlength="100" required></div>
                                <div class="panel-field"><label>معرفی / بیوگرافی مدرس (پروفایل عمومی)</label><textarea class="panel-textarea" name="profile_note" rows="3" maxlength="5000" placeholder="توضیح کوتاه درباره سوابق، تخصص و رزومه مدرس">{{ $user->profile_note }}</textarea></div>
                                <div class="panel-field"><label>وضعیت مدرس</label><select class="panel-select" name="status"><option value="active" @selected($user->is_active)>فعال</option><option value="pending" @selected(! $user->is_active)>در انتظار تأیید</option></select></div>

                                <div class="panel-field"><label>دوره‌های تخصیص‌یافته</label>
                                    <div class="admin-check-list">
                                        @forelse ($courses as $course)
                                            <label class="admin-check-item">
                                                <input type="checkbox" name="course_ids[]" value="{{ $course->id }}" @checked($course->teacher_id === $user->id)>
                                                <span>{{ $course->title }}</span>
                                            </label>
                                        @empty
                                            <span class="panel-muted">دوره‌ای ثبت نشده است.</span>
                                        @endforelse
                                    </div>
                                </div>

                                <div class="panel-field">
                                    <label>دسترسی‌های پنل مدرس</label>
                                    <p class="panel-muted">همین صفحه‌ها و همین پنل برای مدرس استفاده می‌شود؛ فقط منوها و عملیات انتخاب‌شده در دسترس او قرار می‌گیرد.</p>
                                    <div class="admin-check-list">
                                        @foreach ($teacherAccessGroups as $key => $group)
                                            <label class="admin-check-item">
                                                <input type="checkbox" name="teacher_access[]" value="{{ $key }}" @checked($selectedGroups[$key] ?? false)>
                                                <span>{{ $group['label'] }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="panel-field"><label>گذرواژه جدید (اختیاری)</label><input class="panel-input" type="password" name="password" minlength="12" autocomplete="new-password"><span class="panel-muted">برای حفظ گذرواژه فعلی خالی بگذارید.</span></div>
                                <div class="panel-field"><label>تکرار گذرواژه جدید</label><input class="panel-input" type="password" name="password_confirmation" minlength="12" autocomplete="new-password"></div>
                            </x-admin.drawer-form>
                        @endcan
                    @endif
                @empty
                    <tr><td colspan="7" class="panel-empty">موردی برای نمایش وجود ندارد.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="panel-pagination"><span>{{ $users->count() }} از {{ $users->total() }} حساب</span>{{ $users->links() }}</div>
    </section>

    @can('users.manage')
        @if ($role === 'teacher')
            <x-admin::drawer-form id="user-create" title="ساخت مدرس" :action="route($storeRoute)" submit-label="ساخت مدرس">
                <div class="panel-field"><label>نام و نام خانوادگی</label><input class="panel-input" name="name" value="{{ old('name') }}" required maxlength="160"></div>
                <div class="panel-field"><label>موبایل (نام کاربری)</label><input class="panel-input" name="phone" value="{{ old('phone') }}" required maxlength="30" dir="ltr" inputmode="tel" placeholder="09xxxxxxxxx"></div>
                <div class="panel-field"><label>ایمیل (اختیاری)</label><input class="panel-input" type="email" name="email" value="{{ old('email') }}" maxlength="190" dir="ltr"></div>
                <div class="panel-field"><label>درس</label><input class="panel-input" name="subject" value="{{ old('subject') }}" maxlength="100" required placeholder="مثلاً ریاضی"></div>
                <div class="panel-field"><label>معرفی / بیوگرافی مدرس (پروفایل عمومی)</label><textarea class="panel-textarea" name="profile_note" rows="3" maxlength="5000" placeholder="توضیح کوتاه درباره سوابق، تخصص و رزومه مدرس">{{ old('profile_note') }}</textarea></div>
                <div class="panel-field"><label>وضعیت</label><select class="panel-select" name="status"><option value="active">فعال</option><option value="pending">در انتظار تأیید</option></select></div>
                <div class="panel-field"><label>گذرواژه اولیه</label><input class="panel-input" type="password" name="password" minlength="12" required autocomplete="new-password"></div>
                <div class="panel-field"><label>تکرار گذرواژه</label><input class="panel-input" type="password" name="password_confirmation" minlength="12" required autocomplete="new-password"></div>

                <div class="panel-field">
                    <label>دوره‌ها</label>
                    <div class="admin-check-list">
                        @forelse ($courses as $course)
                            <label class="admin-check-item"><input type="checkbox" name="course_ids[]" value="{{ $course->id }}"><span>{{ $course->title }}</span></label>
                        @empty
                            <span class="panel-muted">هنوز دوره‌ای ساخته نشده است.</span>
                        @endforelse
                    </div>
                </div>

                <div class="panel-field">
                    <label>دسترسی‌ها</label>
                    <p class="panel-muted">دسترسی‌های انتخابی، منوها و عملیات همین پنل را برای این مدرس تعیین می‌کنند.</p>
                    <div class="admin-check-list">
                        @foreach ($teacherAccessGroups as $key => $group)
                            <label class="admin-check-item"><input type="checkbox" name="teacher_access[]" value="{{ $key }}" checked><span>{{ $group['label'] }}</span></label>
                        @endforeach
                    </div>
                </div>
                <div class="panel-alert">ارسال پیامک ورود در این نسخه وصل نیست؛ گذرواژه اولیه را به مدرس تحویل دهید.</div>
            </x-admin.drawer-form>
        @else
            <x-admin::drawer-form id="user-create" title="ثبت کاربر" :action="route($storeRoute)" submit-label="ثبت {{ $labels[$role] }}">
                <div class="panel-field"><label>نام و نام خانوادگی</label><input class="panel-input" name="name" value="{{ old('name') }}" required maxlength="160"></div>
                <div class="panel-field"><label>ایمیل ورود</label><input class="panel-input" type="email" name="email" value="{{ old('email') }}" required maxlength="190" dir="ltr"></div>
                <div class="panel-field"><label>شماره تماس</label><input class="panel-input" name="phone" value="{{ old('phone') }}" maxlength="30" dir="ltr"></div>
                <div class="panel-field"><label>گذرواژه اولیه</label><input class="panel-input" type="password" name="password" minlength="12" required autocomplete="new-password"></div>
                <div class="panel-field"><label>تکرار گذرواژه</label><input class="panel-input" type="password" name="password_confirmation" minlength="12" required autocomplete="new-password"></div>
                @if ($role === 'student')
                    <div class="panel-field"><label>پایه تحصیلی</label><select class="panel-select" name="grade" required><option value="">انتخاب پایه</option>@foreach (['دهم','یازدهم','دوازدهم','فارغ‌التحصیل'] as $grade)<option value="{{ $grade }}">{{ $grade }}</option>@endforeach</select></div>
                    <div class="panel-field"><label>شناسه دانش‌آموز در Quiz24 (اختیاری)</label><input class="panel-input" name="external_student_id" maxlength="120" dir="ltr"></div>
                    <div class="panel-field"><label>عضویت در کلاس‌ها</label><select class="panel-select" name="classroom_ids[]" multiple size="6">@foreach ($classrooms as $classroom)<option value="{{ $classroom->id }}">{{ $classroom->name }}</option>@endforeach</select></div>
                @elseif ($role === 'parent')
                    <div class="panel-field">
                        <label>اتصال به دانش‌آموزان</label>
                        <div class="panel-remote-lookup" data-remote-lookup data-lookup-url="{{ route('admin.lookups.students') }}" data-lookup-name="child_ids[]">
                            <input class="panel-input" type="search" data-lookup-input minlength="2" maxlength="80" autocomplete="off" placeholder="حداقل دو حرف از نام یا شماره تماس را وارد کنید">
                            <div class="panel-lookup-results" data-lookup-results role="listbox" aria-label="نتایج جستجوی دانش‌آموزان"></div>
                            <div class="panel-lookup-selected" data-lookup-selected aria-live="polite">
                                @foreach ($selectedChildren as $selectedChild)
                                    <span class="panel-lookup-chip">
                                        <input type="hidden" name="child_ids[]" value="{{ $selectedChild->id }}" data-lookup-value="{{ $selectedChild->id }}">
                                        <span>{{ $selectedChild->name }}</span>
                                        <button type="button" data-lookup-remove aria-label="حذف دانش‌آموز">×</button>
                                    </span>
                                @endforeach
                            </div>
                            <small class="panel-muted">نتایج فقط پس از جستجوی شما و حداکثر ۲۰ مورد در هر درخواست دریافت می‌شوند.</small>
                            <div class="panel-muted" data-lookup-status role="status"></div>
                        </div>
                    </div>
                @endif
            </x-admin.drawer-form>
        @endif
    @endcan
@endsection
