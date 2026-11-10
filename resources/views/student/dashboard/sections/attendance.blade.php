<section class="page" id="s-att"><div class="card"><div class="ch"><h2>حضور و غیاب</h2><span class="tag">ثبت‌شده در سامانه</span></div>
    <div class="tw"><table><thead><tr><th>کلاس</th><th>تاریخ جلسه</th><th>وضعیت</th></tr></thead><tbody>
    @forelse($attendance as $record)<tr><td>{{ $record->classroom?->name ?? 'کلاس' }}</td><td>{{ \App\Support\PersianDate::format($record->session_date, 'Y/m/d') }}</td><td>@if($record->status === 'present')<span class="tag">حاضر</span>@elseif($record->status === 'late')<span class="tag w">با تأخیر</span>@else<span class="tag r">غایب</span>@endif</td></tr>@empty<tr><td colspan="3" class="empty">رکوردی از حضور و غیاب ثبت نشده است.</td></tr>@endforelse
    </tbody></table></div></div></section>
