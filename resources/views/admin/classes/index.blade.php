@extends('admin.layouts.app')

@section('page-title', 'کلاس‌ها')
@section('page-description', 'فهرست کلاس‌ها، مدرس مسئول، زمان‌بندی و اعضا')

@section('content')
    <div class="panel-page-head">
        <div><h2>کلاس‌های آکادمی</h2><p>کلاس‌های ثبت‌شده و برنامهٔ برگزاری</p></div>
        @can('classes.manage')
            <button class="panel-btn" type="button" data-admin-drawer-open="class-create" data-drawer-title="کلاس جدید">＋ کلاس جدید</button>
        @endcan
    </div>

    <section class="panel-card">
        <div class="panel-card-title">
            <div><h3>فهرست کلاس‌ها</h3><p>{{ $classrooms->total() }} کلاس ثبت‌شده</p></div>
        </div>
        <div class="panel-table-wrap">
            <table class="panel-table" data-searchable-table>
                <thead><tr><th>کلاس</th><th>مدرس</th><th>دوره</th><th>زمان‌بندی</th><th>دانش‌آموز</th><th>وضعیت</th><th>لینک ورود</th>@can('classes.manage')<th>عملیات</th>@endcan</tr></thead>
                <tbody>
                @forelse ($classrooms as $classroom)
                    <tr>
                        <td><strong>{{ $classroom->name }}</strong><div class="panel-muted">#{{ $classroom->id }}</div></td>
                        <td>{{ $classroom->teacher->name ?? '—' }}</td>
                        <td>{{ $classroom->course->title ?? '—' }}</td>
                        <td>{{ collect($classroom->schedule['days'] ?? [])->map(fn ($day) => ['sat'=>'شنبه','sun'=>'یکشنبه','mon'=>'دوشنبه','tue'=>'سه‌شنبه','wed'=>'چهارشنبه','thu'=>'پنجشنبه','fri'=>'جمعه'][$day] ?? $day)->join('، ') ?: '—' }} <span class="panel-muted">{{ $classroom->schedule['time'] ?? '' }}</span></td>
                        <td>{{ number_format($classroom->students_count) }}</td>
                        <td><x-admin.badge :variant="$classroom->status === 'active' ? 'success' : 'warning'">{{ ['active'=>'فعال','paused'=>'متوقف','archived'=>'بایگانی'][$classroom->status] ?? $classroom->status }}</x-admin.badge></td>
                        <td>@if ($classroom->meeting_url)<a href="{{ $classroom->meeting_url }}" target="_blank" rel="noopener noreferrer">مشاهده لینک ↗</a>@else<span class="panel-muted">ثبت نشده</span>@endif</td>
                        @can('classes.manage')
                            <td><button class="panel-btn panel-btn-secondary panel-btn-sm" type="button" data-admin-drawer-open="class-edit-{{ $classroom->id }}" data-drawer-title="ویرایش کلاس">ویرایش</button></td>
                        @endcan
                    </tr>
                    @can('classes.manage')
                        <x-admin.drawer-form :id="'class-edit-'.$classroom->id" title="ویرایش کلاس" :action="route('admin.classes.update', $classroom)" submit-label="ذخیره تغییرات">
                            @method('PATCH')
                            <div class="panel-field"><label>نام کلاس</label><input class="panel-input" name="name" value="{{ $classroom->name }}" required maxlength="180"></div>
                            <div class="panel-field"><label>دوره مرتبط</label><select class="panel-select" name="course_id"><option value="">بدون دوره</option>@foreach ($courses as $course)<option value="{{ $course->id }}" @selected($classroom->course_id === $course->id)>{{ $course->title }}</option>@endforeach</select></div>
                            <div class="panel-field"><label>مدرس مسئول</label><select class="panel-select" name="teacher_id" required>@foreach ($teachers as $teacher)<option value="{{ $teacher->id }}" @selected($classroom->teacher_id === $teacher->id)>{{ $teacher->name }}</option>@endforeach</select></div>
                            <div class="panel-field"><label>لینک ورود</label><input class="panel-input" type="url" name="meeting_url" value="{{ $classroom->meeting_url }}" dir="ltr"></div>
                            <div class="panel-field"><label>روزهای برگزاری</label><select class="panel-select" name="schedule_days[]" multiple size="5">@foreach (['sat'=>'شنبه','sun'=>'یکشنبه','mon'=>'دوشنبه','tue'=>'سه‌شنبه','wed'=>'چهارشنبه','thu'=>'پنجشنبه','fri'=>'جمعه'] as $day=>$label)<option value="{{ $day }}" @selected(in_array($day, $classroom->schedule['days'] ?? [], true))>{{ $label }}</option>@endforeach</select></div>
                            <div class="panel-field"><label>ساعت شروع</label><input class="panel-input" type="time" name="schedule_time" value="{{ $classroom->schedule['time'] ?? '' }}"></div>
                            <div class="panel-field"><label>دانش‌آموزان</label><select class="panel-select" name="student_ids[]" multiple size="6">@foreach ($students as $student)<option value="{{ $student->id }}" @selected($classroom->students->contains('id', $student->id))>{{ $student->name }}</option>@endforeach</select></div>
                            <div class="panel-field"><label>وضعیت</label><select class="panel-select" name="status">@foreach (['active'=>'فعال','paused'=>'متوقف','archived'=>'بایگانی'] as $key=>$label)<option value="{{ $key }}" @selected($classroom->status === $key)>{{ $label }}</option>@endforeach</select></div>
                        </x-admin.drawer-form>
                    @endcan
                @empty
                    <tr><td colspan="8" class="panel-empty">کلاسی برای نمایش وجود ندارد.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="panel-pagination"><span>{{ $classrooms->count() }} از {{ $classrooms->total() }} کلاس</span>{{ $classrooms->links() }}</div>
    </section>

    @can('classes.manage')
        <x-admin.drawer-form id="class-create" title="کلاس جدید" :action="route('admin.classes.store')" submit-label="ثبت کلاس">
            <div class="panel-field"><label>نام کلاس</label><input class="panel-input" name="name" value="{{ old('name') }}" required maxlength="180"></div>
            <div class="panel-field"><label>دوره مرتبط</label><select class="panel-select" name="course_id"><option value="">بدون دوره</option>@foreach ($courses as $course)<option value="{{ $course->id }}">{{ $course->title }}</option>@endforeach</select></div>
            <div class="panel-field"><label>مدرس مسئول</label><select class="panel-select" name="teacher_id" required><option value="">انتخاب مدرس</option>@foreach ($teachers as $teacher)<option value="{{ $teacher->id }}">{{ $teacher->name }}</option>@endforeach</select></div>
            <div class="panel-field"><label>لینک ورود</label><input class="panel-input" type="url" name="meeting_url" dir="ltr" placeholder="https://..."></div>
            <div class="panel-field"><label>روزهای برگزاری</label><select class="panel-select" name="schedule_days[]" multiple size="5">@foreach (['sat'=>'شنبه','sun'=>'یکشنبه','mon'=>'دوشنبه','tue'=>'سه‌شنبه','wed'=>'چهارشنبه','thu'=>'پنجشنبه','fri'=>'جمعه'] as $day=>$label)<option value="{{ $day }}">{{ $label }}</option>@endforeach</select></div>
            <div class="panel-field"><label>ساعت شروع</label><input class="panel-input" type="time" name="schedule_time"></div>
            <div class="panel-field"><label>دانش‌آموزان</label><select class="panel-select" name="student_ids[]" multiple size="6">@foreach ($students as $student)<option value="{{ $student->id }}">{{ $student->name }}</option>@endforeach</select></div>
            <div class="panel-field"><label>وضعیت</label><select class="panel-select" name="status"><option value="active">فعال</option><option value="paused">متوقف</option><option value="archived">بایگانی</option></select></div>
        </x-admin.drawer-form>
    @endcan
@endsection
