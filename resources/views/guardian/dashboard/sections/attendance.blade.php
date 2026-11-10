<section class="page" id="p-att"><div class="card"><div class="ch"><h2>حضور و غیاب · {{ $selected_child?->name ?? 'فرزند' }}</h2><span class="tag">{{ $attendance_rate === null ? 'بدون داده' : $faNumber($attendance_rate).'٪ حضور' }}</span></div>
    <div class="cal"><b>تاریخ</b><b>کلاس</b><b>وضعیت</b></div>
    @forelse($attendance as $record)<div class="it"><div class="th c"></div><div><b>{{ $record->classroom?->name ?? 'کلاس' }}</b><span>{{ \App\Support\PersianDate::format($record->session_date, 'l، d F Y') }}</span></div>@if($record->status==='present')<span class="tag">حاضر</span>@elseif($record->status==='late')<span class="tag w">با تأخیر</span>@else<span class="tag r">غایب</span>@endif</div>
    @empty<div class="empty">رکورد حضور و غیابی ثبت نشده است.</div>@endforelse
</div></section>
