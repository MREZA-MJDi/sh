@extends('layouts.panel')

@section('page-title', 'حضور و غیاب')
@section('page-description', 'ثبت وضعیت هر دانش‌آموز برای یک جلسه مشخص، همراه با زمان ورود و مدت حضور واقعی')

@section('content')
    <section class="panel-card mb-4">
        <form method="GET" action="{{ route('admin.attendance.index') }}" class="panel-form-grid panel-form-grid-4 items-end">
            <div class="panel-field">
                <label for="classroom_id">کلاس</label>
                <select class="panel-select" id="classroom_id" name="classroom_id" required>
                    <option value="">انتخاب کلاس</option>
                    @foreach ($classrooms as $item)
                        <option value="{{ $item->id }}" @selected($classroom?->id === $item->id)>{{ $item->name }} — {{ $item->teacher->name ?? 'بدون مدرس' }}</option>
                    @endforeach
                </select>
            </div>
            <div class="panel-field">
                <label for="date">تاریخ جلسه</label>
                <input class="panel-input" id="date" type="date" name="date" value="{{ $date }}" required>
            </div>
            <div class="panel-field">
                <label for="class_session_id">جلسه مشخص</label>
                <select class="panel-select" id="class_session_id" name="class_session_id">
                    <option value="">اولین جلسه این تاریخ</option>
                    @foreach ($sessions as $item)
                        <option value="{{ $item->id }}" @selected($session?->id === $item->id)>
                            {{ $item->title }} · {{ \App\Support\PersianDate::format($item->starts_at, 'H:i') }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="panel-form-actions"><button class="panel-btn panel-btn-secondary" type="submit">نمایش فهرست</button></div>
        </form>
    </section>

    @if ($classroom && $sessions->isEmpty())
        <section class="panel-card panel-empty">
            برای این کلاس در تاریخ انتخاب‌شده جلسه‌ای ثبت نشده است. ابتدا جلسه را از بخش کلاس‌ها و اسکای‌روم تعریف کنید؛ زمان جلسه از روی داده‌ی واقعی آن محاسبه می‌شود.
            <div class="mt-3"><a class="panel-btn panel-btn-secondary" href="{{ route('admin.classes.index') }}">رفتن به مدیریت کلاس‌ها</a></div>
        </section>
    @elseif ($classroom && $session)
        <section class="panel-card mb-4">
            <div class="panel-card-title">
                <div>
                    <h3>ثبت حضور کلاس {{ $classroom->name }}</h3>
                    <p>
                        {{ $session->title }} ·
                        {{ \App\Support\PersianDate::format($session->starts_at, 'Y/m/d H:i') }}
                        @if ($session->ends_at)
                            تا {{ \App\Support\PersianDate::format($session->ends_at, 'H:i') }}
                        @endif
                        · {{ $session->ends_at ? max(0, intdiv($session->ends_at->getTimestamp() - $session->starts_at->getTimestamp(), 60)).' دقیقه برنامه‌ریزی‌شده' : 'مدت برنامه‌ریزی‌شده ثبت نشده' }}
                    </p>
                </div>
                <span class="panel-badge">ثبت برای همین جلسه</span>
            </div>

            @if ($classroom->students->isNotEmpty())
                <form method="POST" action="{{ route('admin.attendance.store') }}">
                    @csrf
                    <input type="hidden" name="classroom_id" value="{{ $classroom->id }}">
                    <input type="hidden" name="class_session_id" value="{{ $session->id }}">
                    <div class="panel-table-wrap">
                        <table class="panel-table">
                            <thead><tr><th>دانش‌آموز</th><th>وضعیت حضور</th><th>زمان واقعی ورود</th><th>مدت حضور (دقیقه)</th><th>یادداشت</th></tr></thead>
                            <tbody>
                            @foreach ($classroom->students as $student)
                                @php($record = $records->get($student->id))
                                <tr>
                                    <td><strong>{{ $student->name }}</strong><div class="panel-muted">{{ $student->phone ?: $student->email }}</div></td>
                                    <td>
                                        <select class="panel-select" name="attendance[{{ $student->id }}][status]" required>
                                            @foreach (['present'=>'حاضر','absent'=>'غایب','late'=>'با تأخیر','excused'=>'موجه'] as $key=>$label)
                                                <option value="{{ $key }}" @selected(($record->status ?? 'present') === $key)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td><input class="panel-input" type="datetime-local" name="attendance[{{ $student->id }}][joined_at]" value="{{ old('attendance.'.$student->id.'.joined_at', $record?->joined_at?->format('Y-m-d\TH:i')) }}"></td>
                                    <td><input class="panel-input" type="number" min="0" max="1440" name="attendance[{{ $student->id }}][attended_minutes]" value="{{ old('attendance.'.$student->id.'.attended_minutes', $record?->attended_minutes) }}" placeholder="نامشخص"></td>
                                    <td><input class="panel-input" name="attendance[{{ $student->id }}][note]" value="{{ old('attendance.'.$student->id.'.note', $record?->note) }}" maxlength="500" placeholder="یادداشت اختیاری"></td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="panel-form-actions"><button class="panel-btn" type="submit">ذخیره حضور و غیاب جلسه</button></div>
                </form>
            @else
                <div class="panel-empty">برای این کلاس هنوز دانش‌آموزی ثبت‌نام نشده است.</div>
            @endif
        </section>
    @else
        <div class="panel-card panel-empty">برای شروع، یک کلاس و جلسه انتخاب کنید.</div>
    @endif

    <section class="panel-card">
        <div class="panel-card-title"><div><h3>سوابق ثبت‌شده</h3><p>مدت برنامه‌ریزی‌شده و مدت حضور فقط از داده‌های ثبت‌شده محاسبه می‌شوند.</p></div></div>
        <div class="panel-table-wrap">
            <table class="panel-table" data-searchable-table>
                <thead><tr><th>دانش‌آموز</th><th>کلاس / جلسه</th><th>تاریخ</th><th>وضعیت</th><th>مدت برنامه</th><th>مدت حضور</th><th>تأخیر</th><th>ثبت‌کننده</th></tr></thead>
                <tbody>
                @forelse ($history as $record)
                    <tr>
                        <td>{{ $record->student->name ?? '—' }}</td>
                        <td>{{ $record->classroom->name ?? '—' }}<div class="panel-muted">{{ $record->session?->title ?? 'جلسه قدیمی / نامشخص' }}</div></td>
                        <td>{{ $record->session_date->format('Y-m-d') }}</td>
                        <td><span class="panel-badge {{ in_array($record->status, ['present','late']) ? 'panel-badge-success' : ($record->status==='absent' ? 'panel-badge-danger' : 'panel-badge-warning') }}">{{ ['present'=>'حاضر','absent'=>'غایب','late'=>'با تأخیر','excused'=>'موجه'][$record->status] ?? $record->status }}</span></td>
                        <td>{{ $record->scheduled_minutes !== null ? $record->scheduled_minutes.' دقیقه' : 'ثبت نشده' }}</td>
                        <td>{{ $record->attended_minutes !== null ? $record->attended_minutes.' دقیقه' : 'ثبت نشده' }}</td>
                        <td>{{ $record->late_minutes !== null ? $record->late_minutes.' دقیقه' : 'نامشخص' }}</td>
                        <td>{{ $record->marker->name ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="panel-empty">برای این جلسه رکوردی ثبت نشده است.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="panel-pagination"><span>{{ $history->count() }} رکورد</span>{{ $history->links() }}</div>
    </section>
@endsection