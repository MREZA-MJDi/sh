@extends('layouts.parent')

@section('title', 'جلسات اولیا')

@section('content')
<x-crumbs :items="['کارتابل اولیا' => route('guardian.dashboard'), 'جلسات اولیا' => null]"/>
<x-card title="درخواست جلسه با آکادمی">
    @can('meetings.create')
    @if($children->isNotEmpty())
    <form method="POST" action="{{ route('guardian.meetings.store') }}" class="fm">
        @csrf
        <div><label>فرزند</label><select class="fld" name="student_id" id="parent-meeting-student" required>@foreach($children as $child)<option value="{{ $child->id }}">{{ $child->name }} · {{ $child->grade ?? 'پایه نامشخص' }}</option>@endforeach</select><x-field-error name="student_id"/></div>
        <div><label>نوع جلسه</label><select class="fld" name="meeting_type"><option value="in_person">حضوری</option><option value="phone">تلفنی</option><option value="online">آنلاین</option></select></div>
        <div class="w"><label>موضوع جلسه</label><input class="fld" name="title" maxlength="180" required><x-field-error name="title"/></div>
        <div><label>کلاس مرتبط (اختیاری)</label><select class="fld" name="classroom_id" id="parent-meeting-class"><option value="">عمومی</option>@foreach($classrooms as $classroom)@foreach($classroom->students as $member)<option value="{{ $classroom->id }}" data-student="{{ $member->id }}">{{ $classroom->name }}</option>@endforeach @endforeach</select></div>
        <div><label>زمان پیشنهادی</label><input class="fld" type="datetime-local" name="proposed_at"><x-field-error name="proposed_at"/></div>
        <div class="w"><label>شرح موضوع</label><textarea class="fld" name="agenda" maxlength="5000"></textarea><x-field-error name="agenda"/></div>
        <div class="w frm"><button class="btn" type="submit">ثبت درخواست جلسه</button></div>
    </form>
    @else
        <div class="empty">برای ثبت جلسه، ابتدا باید دانش‌آموزی به حساب شما متصل شود.</div>
    @endif
    @else
        <div class="empty">امکان ثبت درخواست جلسه برای این حساب فعال نیست.</div>
    @endcan
</x-card>
<script>
(() => {
    const student = document.getElementById('parent-meeting-student');
    const classroom = document.getElementById('parent-meeting-class');
    if (!student || !classroom) return;
    const options = [...classroom.options];
    const filter = () => {
        classroom.replaceChildren(options[0].cloneNode(true));
        options.slice(1).filter(option => option.dataset.student === student.value)
            .forEach(option => classroom.add(option.cloneNode(true)));
    };
    student.addEventListener('change', filter);
    filter();
})();
</script>
<x-card title="جلسات و درخواست‌های من">
    @forelse($meetings as $meeting)
        <div class="trk">
            <div class="th t-a"><x-icon name="calendar"/></div>
            <div><b>{{ $meeting->title }}</b><span class="mu">{{ $meeting->student?->name }} · {{ $meeting->scheduled_at?->format('Y/m/d H:i') ?? ($meeting->proposed_at?->format('Y/m/d H:i') ?? 'در انتظار هماهنگی زمان') }}</span></div>
            <x-tag>{{ ['requested'=>'ثبت درخواست','proposed'=>'پیشنهاد زمان','scheduled'=>'هماهنگ‌شده','accepted'=>'تأیید ولی','declined'=>'ردشده','completed'=>'انجام‌شده','cancelled'=>'لغوشده'][$meeting->status] ?? $meeting->status }}</x-tag>
            @if($meeting->meeting_url && in_array($meeting->status, ['proposed', 'accepted'], true))
                <a class="btn g s" href="{{ $meeting->meeting_url }}" target="_blank" rel="noopener noreferrer">ورود به جلسه آنلاین</a>
            @endif
            @if($meeting->status === 'proposed')
                <form method="POST" action="{{ route('guardian.meetings.respond', $meeting) }}">@csrf<input type="hidden" name="response" value="accepted"><button class="btn s" type="submit">تأیید زمان</button></form>
                <form method="POST" action="{{ route('guardian.meetings.respond', $meeting) }}">@csrf<input type="hidden" name="response" value="declined"><button class="btn g" type="submit">عدم تأیید</button></form>
            @endif
        </div>
    @empty <div class="empty">هنوز جلسه‌ای ثبت نشده است.</div> @endforelse
    @if($meetings->hasPages())<div class="frm">{{ $meetings->links() }}</div>@endif
</x-card>
@endsection