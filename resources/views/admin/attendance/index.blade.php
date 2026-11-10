@extends('layouts.panel')

@section('page-title', 'حضور و غیاب')
@section('page-description', 'ثبت و مشاهده وضعیت حضور دانش‌آموزان هر کلاس بر اساس تاریخ جلسه')

@section('content')
    <section class="panel-card mb-4">
        <form method="GET" action="{{ route('admin.attendance.index') }}" class="panel-form-grid panel-form-grid-3 items-end">
            <div class="panel-field">
                <label for="classroom_id">کلاس</label>
                <select class="panel-select" id="classroom_id" name="classroom_id" required>
                    <option value="">انتخاب کلاس</option>
                    @foreach ($classrooms as $item)
                        <option value="{{ $item->id }}" @selected($classroom?->id === $item->id)>{{ $item->name }} — {{ $item->teacher->name ?? 'بدون مدرس' }}</option>
                    @endforeach
                </select>
            </div>
            <div class="panel-field"><label for="date">تاریخ جلسه</label><input class="panel-input" id="date" type="date" name="date" value="{{ $date }}" required></div>
            <div class="panel-form-actions"><button class="panel-btn panel-btn-secondary" type="submit">نمایش فهرست</button></div>
        </form>
    </section>

    @if ($classroom)
        <section class="panel-card mb-4">
            <div class="panel-card-title">
                <div><h3>ثبت حضور کلاس {{ $classroom->name }}</h3><p>تاریخ جلسه: {{ $date }} · {{ $classroom->students->count() }} دانش‌آموز ثبت‌نام‌شده</p></div>
                <span class="panel-badge">ذخیره گروهی</span>
            </div>

            @if ($classroom->students->isNotEmpty())
                <form method="POST" action="{{ route('admin.attendance.store') }}">
                    @csrf
                    <input type="hidden" name="classroom_id" value="{{ $classroom->id }}">
                    <input type="hidden" name="session_date" value="{{ $date }}">
                    <div class="panel-table-wrap">
                        <table class="panel-table">
                            <thead><tr><th>دانش‌آموز</th><th>وضعیت حضور</th><th>یادداشت</th></tr></thead>
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
                                    <td><input class="panel-input" name="attendance[{{ $student->id }}][note]" value="{{ $record->note ?? '' }}" maxlength="500" placeholder="یادداشت اختیاری"></td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="panel-form-actions"><button class="panel-btn" type="submit">ذخیره حضور و غیاب</button></div>
                </form>
            @else
                <div class="panel-empty">برای این کلاس هنوز دانش‌آموزی ثبت‌نام نشده است.</div>
            @endif
        </section>
    @else
        <div class="panel-card panel-empty">برای شروع، یک کلاس انتخاب کنید.</div>
    @endif

    <section class="panel-card">
        <div class="panel-card-title"><div><h3>سوابق ثبت‌شده</h3><p>سوابق همان کلاس و تاریخ انتخاب‌شده</p></div></div>
        <div class="panel-table-wrap">
            <table class="panel-table" data-searchable-table>
                <thead><tr><th>دانش‌آموز</th><th>کلاس</th><th>تاریخ</th><th>وضعیت</th><th>یادداشت</th><th>ثبت‌کننده</th></tr></thead>
                <tbody>
                @forelse ($history as $record)
                    <tr>
                        <td>{{ $record->student->name ?? '—' }}</td>
                        <td>{{ $record->classroom->name ?? '—' }}</td>
                        <td>{{ $record->session_date->format('Y-m-d') }}</td>
                        <td><span class="panel-badge {{ in_array($record->status, ['present','late']) ? 'panel-badge-success' : ($record->status==='absent' ? 'panel-badge-danger' : 'panel-badge-warning') }}">{{ ['present'=>'حاضر','absent'=>'غایب','late'=>'با تأخیر','excused'=>'موجه'][$record->status] ?? $record->status }}</span></td>
                        <td>{{ $record->note ?: '—' }}</td>
                        <td>{{ $record->marker->name ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="panel-empty">برای این تاریخ رکوردی ثبت نشده است.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="panel-pagination"><span>{{ $history->count() }} رکورد</span>{{ $history->links() }}</div>
    </section>
@endsection
