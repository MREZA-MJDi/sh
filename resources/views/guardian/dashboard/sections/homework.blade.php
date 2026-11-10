<section class="page" id="p-hw"><div class="card"><div class="ch"><h2>تکالیف و نتایج بررسی</h2><span class="tag w">{{ $faNumber($pending_homework_count) }} باز</span></div>
    <div class="tw"><table><thead><tr><th>تکلیف</th><th>مهلت</th><th>وضعیت</th><th>نتیجه</th></tr></thead><tbody>
    @forelse($homeworks as $homework)<tr><td>{{ $homework->title }}</td><td>{{ $homework->due_at ? \App\Support\PersianDate::format($homework->due_at, 'Y/m/d H:i') : 'بدون مهلت' }}</td><td>@if($homework->selected_student_submission_count > 0)<span class="tag d">ارسال شده</span>@else<span class="tag w">ارسال نشده</span>@endif</td><td>—</td></tr>
    @empty<tr><td colspan="4" class="empty">تکلیف منتشرشده‌ای برای این فرزند ثبت نشده است.</td></tr>@endforelse
    </tbody></table></div>
</div>
<div class="card"><div class="ch"><h2>نتایج بررسی تکالیف</h2><span class="tag">{{ $faNumber($reviewed_homework->count()) }} نتیجه</span></div>
    @forelse($reviewed_homework as $submission)<article class="news"><b>{{ $submission->homework?->title ?? 'تکلیف' }}</b><span class="mu">بررسی توسط {{ $submission->reviewer?->name ?? 'مدرس' }} · {{ \App\Support\PersianDate::format($submission->reviewed_at, 'Y/m/d') }}</span>
        @if($submission->score !== null)<strong>نمره {{ $faNumber($submission->score,2) }}</strong>@endif
        @if(filled($submission->feedback))<p>{{ $submission->feedback }}</p>@endif
    </article>@empty<div class="empty">هنوز نتیجه بررسی‌شده‌ای ثبت نشده است.</div>@endforelse
</div></section>
