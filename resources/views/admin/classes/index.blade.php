@extends('layouts.panel')

@section('page-title', 'کلاس‌ها و جلسات')
@section('page-description', 'ساخت کلاس، تخصیص مدرس و دانش‌آموز و مدیریت لینک ورود')

@section('content')
    <section class="panel-card mb-4">
        <div class="panel-card-title"><div><h3>ایجاد کلاس جدید</h3><p>هر کلاس به یک مدرس اختصاص می‌یابد؛ عضویت دانش‌آموزان از همین‌جا مدیریت می‌شود.</p></div></div>
        <form method="POST" action="{{ route('admin.classes.store') }}">
            @csrf
            <div class="panel-form-grid panel-form-grid-3">
                <div class="panel-field"><label>نام کلاس</label><input class="panel-input" name="name" value="{{ old('name') }}" required maxlength="180"></div>
                <div class="panel-field"><label>دوره مرتبط</label><select class="panel-select" name="course_id"><option value="">بدون دوره</option>@foreach ($courses as $course)<option value="{{ $course->id }}">{{ $course->title }}</option>@endforeach</select></div>
                <div class="panel-field"><label>مدرس مسئول</label><select class="panel-select" name="teacher_id" required><option value="">انتخاب مدرس</option>@foreach ($teachers as $teacher)<option value="{{ $teacher->id }}">{{ $teacher->name }}</option>@endforeach</select></div>
                <div class="panel-field"><label>لینک ورود کلاس</label><input class="panel-input" type="url" name="meeting_url" value="{{ old('meeting_url') }}" dir="ltr" placeholder="https://..."></div>
                <div class="panel-field"><label>روزهای برگزاری</label><select class="panel-select" name="schedule_days[]" multiple size="3"><option value="sat">شنبه</option><option value="sun">یکشنبه</option><option value="mon">دوشنبه</option><option value="tue">سه‌شنبه</option><option value="wed">چهارشنبه</option><option value="thu">پنجشنبه</option><option value="fri">جمعه</option></select></div>
                <div class="panel-field"><label>ساعت شروع</label><input class="panel-input" type="time" name="schedule_time" value="{{ old('schedule_time') }}"></div>
                <div class="panel-field"><label>وضعیت کلاس</label><select class="panel-select" name="status"><option value="active">فعال</option><option value="paused">متوقف</option><option value="archived">بایگانی</option></select></div>
                <div class="panel-field"><label>دانش‌آموزان</label><select class="panel-select" name="student_ids[]" multiple size="4">@foreach ($students as $student)<option value="{{ $student->id }}">{{ $student->name }}</option>@endforeach</select></div>
            </div>
            <div class="panel-form-actions"><button class="panel-btn" type="submit">ساخت کلاس</button></div>
        </form>
    </section>

    <section class="panel-card">
        <div class="panel-card-title"><div><h3>کلاس‌های ثبت‌شده</h3><p>اطلاعات واقعی کلاس‌ها و تعداد اعضا</p></div><span class="panel-badge">{{ $classrooms->total() }} کلاس</span></div>
        <div class="panel-table-wrap">
            <table class="panel-table" data-searchable-table>
                <thead><tr><th>کلاس</th><th>مدرس</th><th>دوره</th><th>دانش‌آموز</th><th>وضعیت</th><th>لینک</th><th>عملیات</th></tr></thead>
                <tbody>
                @forelse ($classrooms as $classroom)
                    <tr>
                        <td><strong>{{ $classroom->name }}</strong><div class="panel-muted">#{{ $classroom->id }}</div></td>
                        <td>{{ $classroom->teacher->name }}</td>
                        <td>{{ $classroom->course->title ?? '—' }}</td>
                        <td>{{ $classroom->students_count }}</td>
                        <td><span class="panel-badge {{ $classroom->status==='active' ? 'panel-badge-success' : 'panel-badge-warning' }}">{{ ['active'=>'فعال','paused'=>'متوقف','archived'=>'بایگانی'][$classroom->status] ?? $classroom->status }}</span></td>
                        <td>@if ($classroom->meeting_url)<a href="{{ $classroom->meeting_url }}" target="_blank" rel="noopener noreferrer" class="panel-muted">مشاهده لینک ↗</a>@else<span class="panel-badge panel-badge-warning">ثبت نشده</span>@endif</td>
                        <td>
                            <details class="panel-details">
                                <summary>ویرایش</summary>
                                <div class="panel-details-content">
                                    <form method="POST" action="{{ route('admin.classes.update', $classroom) }}" class="grid gap-2">
                                        @csrf @method('PATCH')
                                        <div class="panel-field"><label>نام کلاس</label><input class="panel-input" name="name" value="{{ $classroom->name }}" required></div>
                                        <div class="panel-field"><label>مدرس</label><select class="panel-select" name="teacher_id" required>@foreach ($teachers as $teacher)<option value="{{ $teacher->id }}" @selected($classroom->teacher_id===$teacher->id)>{{ $teacher->name }}</option>@endforeach</select></div>
                                        <div class="panel-field"><label>دوره</label><select class="panel-select" name="course_id"><option value="">بدون دوره</option>@foreach ($courses as $course)<option value="{{ $course->id }}" @selected($classroom->course_id===$course->id)>{{ $course->title }}</option>@endforeach</select></div>
                                        <div class="panel-field"><label>لینک ورود</label><input class="panel-input" name="meeting_url" type="url" value="{{ $classroom->meeting_url }}" dir="ltr"></div>
                                        <div class="panel-field"><label>روزهای برگزاری</label>
                                            <select class="panel-select" name="schedule_days[]" multiple size="4">
                                                @foreach (['sat'=>'شنبه','sun'=>'یکشنبه','mon'=>'دوشنبه','tue'=>'سه‌شنبه','wed'=>'چهارشنبه','thu'=>'پنجشنبه','fri'=>'جمعه'] as $day=>$label)
                                                    <option value="{{ $day }}" @selected(in_array($day, $classroom->schedule['days'] ?? [], true))>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="panel-field"><label>ساعت شروع</label><input class="panel-input" type="time" name="schedule_time" value="{{ $classroom->schedule['time'] ?? '' }}"></div>
                                        <div class="panel-field"><label>دانش‌آموزان کلاس</label>
                                            <select class="panel-select" name="student_ids[]" multiple size="5">
                                                @foreach ($students as $student)
                                                    <option value="{{ $student->id }}" @selected($classroom->students->contains('id', $student->id))>{{ $student->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="panel-field"><label>وضعیت</label><select class="panel-select" name="status">@foreach (['active'=>'فعال','paused'=>'متوقف','archived'=>'بایگانی'] as $key=>$value)<option value="{{ $key }}" @selected($classroom->status===$key)>{{ $value }}</option>@endforeach</select></div>
                                        <button class="panel-btn panel-btn-sm" type="submit">ذخیره</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.classes.destroy', $classroom) }}" data-confirm="حذف کلاس؟ سوابق تکالیف مانع حذف کلاس می‌شوند." class="mt-2">
                                        @csrf @method('DELETE')
                                        <button class="panel-btn panel-btn-danger panel-btn-sm" type="submit">حذف کلاس</button>
                                    </form>
                                </div>
                            </details>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="panel-empty">کلاسی ثبت نشده است.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="panel-pagination"><span>{{ $classrooms->count() }} از {{ $classrooms->total() }} کلاس</span>{{ $classrooms->links() }}</div>
    </section>
@endsection
