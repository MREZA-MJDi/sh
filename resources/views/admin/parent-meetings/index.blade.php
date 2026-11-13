@extends('layouts.panel')

@section('page-title', 'جلسات اولیا')
@section('page-description', 'هماهنگی جلسات آقای شیخان با اولیا و کلاس‌های مرتبط')

@section('content')
<section class="panel-card">
    <div class="panel-card-title"><div><h3>ثبت جلسه جدید</h3><p>جلسه را برای ولی و فرزند متصل به او تعریف کنید.</p></div></div>
    <form method="POST" action="{{ route('admin.parent-meetings.store') }}" class="grid gap-2" style="padding:16px">
        @csrf
        <div class="grid grid-cols-2 gap-2">
            <div class="panel-field"><label>ولی</label><select class="panel-select" name="guardian_id" id="meeting-guardian" required><option value="">انتخاب ولی</option>@foreach($guardians as $guardian)<option value="{{ $guardian->id }}">{{ $guardian->name }}</option>@endforeach</select></div>
            <div class="panel-field"><label>دانش‌آموز</label><select class="panel-select" name="student_id" id="meeting-student" required><option value="">ابتدا ولی را انتخاب کنید</option>@foreach($guardians as $guardian)@foreach($guardian->children as $child)<option value="{{ $child->id }}" data-guardian="{{ $guardian->id }}">{{ $child->name }} · {{ $guardian->name }}</option>@endforeach @endforeach</select></div>
        </div>
        <div class="grid grid-cols-2 gap-2">
            <div class="panel-field"><label>کلاس مرتبط (اختیاری)</label><select class="panel-select" name="classroom_id" id="meeting-class"><option value="">جلسه عمومی</option>@foreach($classrooms as $classroom)@foreach($classroom->students as $member)<option value="{{ $classroom->id }}" data-student="{{ $member->id }}">{{ $classroom->name }}</option>@endforeach @endforeach</select></div>
            <div class="panel-field"><label>عنوان جلسه</label><input class="panel-input" name="title" maxlength="180" required></div>
            <div class="panel-field"><label>نوع جلسه</label><select class="panel-select" name="meeting_type"><option value="in_person">حضوری</option><option value="phone">تلفنی</option><option value="online">آنلاین</option></select></div>
            <div class="panel-field"><label>لینک جلسه آنلاین (اختیاری)</label><input class="panel-input" type="url" name="meeting_url" dir="ltr" placeholder="https://..."></div>
        </div>
        <div class="grid grid-cols-2 gap-2">
            <div class="panel-field"><label>زمان پیشنهادی</label><input class="panel-input" type="datetime-local" name="scheduled_at"></div>
            <div class="panel-field"><label>پایان جلسه</label><input class="panel-input" type="datetime-local" name="ends_at"></div>
        </div>
        <div class="panel-field"><label>شرح جلسه</label><textarea class="panel-textarea" name="agenda" maxlength="5000"></textarea></div>
        <button class="panel-btn panel-btn-sm" type="submit">ثبت جلسه</button>
    </form>
    <script>
        (() => {
            const guardian = document.getElementById('meeting-guardian');
            const student = document.getElementById('meeting-student');
            const classroom = document.getElementById('meeting-class');
            if (!guardian || !student || !classroom) return;
            const studentOptions = [...student.options];
            const classOptions = [...classroom.options];
            const filterClasses = () => {
                classroom.replaceChildren(classOptions[0].cloneNode(true));
                classOptions.slice(1).filter(option => option.dataset.student === student.value)
                    .forEach(option => classroom.add(option.cloneNode(true)));
            };
            guardian.addEventListener('change', () => {
                student.replaceChildren(studentOptions[0].cloneNode(true));
                studentOptions.slice(1).filter(option => option.dataset.guardian === guardian.value)
                    .forEach(option => student.add(option.cloneNode(true)));
                filterClasses();
            });
            student.addEventListener('change', filterClasses);
            filterClasses();
        })();
    </script>
    <div class="panel-card-title"><div><h3>کارتابل جلسات</h3><p>درخواست‌ها را بررسی و زمان قطعی جلسه را تعیین کنید.</p></div><span class="panel-badge">{{ $meetings->total() }} جلسه</span></div>
    <div class="panel-table-wrap"><table class="panel-table">
        <thead><tr><th>ولی</th><th>دانش‌آموز</th><th>کلاس</th><th>موضوع</th><th>زمان پیشنهادی</th><th>وضعیت و زمان نهایی</th></tr></thead>
        <tbody>
        @forelse($meetings as $meeting)
            <tr>
                <td>{{ $meeting->guardian?->name ?? '—' }}</td><td>{{ $meeting->student?->name ?? '—' }}</td><td>{{ $meeting->classroom?->name ?? 'عمومی' }}</td>
                <td><strong>{{ $meeting->title }}</strong><div class="panel-muted">{{ $meeting->agenda }}</div></td>
                <td>{{ ($meeting->proposed_at ?? $meeting->scheduled_at)?->format('Y/m/d H:i') ?? '—' }}</td>
                <td><form method="POST" action="{{ route('admin.parent-meetings.update', $meeting) }}" class="grid gap-2">
                    @csrf @method('PATCH')
                    <select class="panel-select" name="status">@foreach(['requested'=>'درخواست ثبت‌شده','proposed'=>'پیشنهاد زمان برای تأیید ولی','accepted'=>'تأییدشده توسط ولی','declined'=>'ردشده توسط ولی','completed'=>'انجام‌شده','cancelled'=>'لغوشده'] as $key=>$label)<option value="{{ $key }}" @selected($meeting->status===$key)>{{ $label }}</option>@endforeach</select>
                    <input class="panel-input" type="url" name="meeting_url" value="{{ $meeting->meeting_url }}" dir="ltr" placeholder="لینک جلسه آنلاین">
                    <input class="panel-input" type="datetime-local" name="scheduled_at" value="{{ $meeting->scheduled_at?->format('Y-m-d\\TH:i') }}">
                    <input class="panel-input" type="datetime-local" name="ends_at" value="{{ $meeting->ends_at?->format('Y-m-d\\TH:i') }}">
                    <textarea class="panel-textarea" name="admin_notes" placeholder="یادداشت داخلی">{{ $meeting->admin_notes }}</textarea>
                    <button class="panel-btn panel-btn-sm">ذخیره</button>
                </form></td>
            </tr>
        @empty <tr><td colspan="6" class="panel-empty">جلسه‌ای ثبت نشده است.</td></tr> @endforelse
        </tbody>
    </table></div>
    <div class="panel-pagination">{{ $meetings->links() }}</div>
</section>
@endsection