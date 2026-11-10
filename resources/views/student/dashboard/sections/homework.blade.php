<section class="page" id="s-hw"><div class="card"><div class="ch"><h2>تکالیف</h2><span class="tag w">{{ $faNumber($pending_homework_count) }} باز</span></div><div class="tw"><table><thead><tr><th>تکلیف</th><th>درس</th><th>مهلت</th><th>وضعیت</th><th>نتیجه بررسی</th></tr></thead><tbody>
    @forelse($homeworks as $homework)
        @php($submission = $homework->submissions->first())
        <tr><td>{{ $homework->title }}</td><td>{{ $homework->classroom?->name ?? '—' }}</td><td>{{ $homework->due_at ? \App\Support\PersianDate::format($homework->due_at, 'Y/m/d H:i') : 'بدون مهلت' }}</td>
            <td>@if($submission?->reviewed_at || $submission?->status === 'reviewed')<span class="tag">بررسی شد</span>@elseif($submission)<span class="tag d">در حال بررسی</span>@else<span class="tag w">در انتظار ارسال</span>@endif</td>
            <td>@if($submission?->reviewed_at){{ $submission->score === null ? 'بررسی شد' : $faNumber($submission->score, 2) }}@if(filled($submission->feedback))<div class="mu">{{ $submission->feedback }}</div>@endif @else—@endif</td></tr>
    @empty<tr><td colspan="5" class="empty">تکلیف منتشرشده‌ای برای حساب تو ثبت نشده است.</td></tr>@endforelse
</tbody></table></div></div>
    @can('homework.submit')
    <div class="card"><div class="ch"><h2>بارگذاری تکلیف</h2></div>
        <form method="POST" action="{{ $homeworks->first() ? route('student.homework.submissions.store', $homeworks->first()) : '#' }}" enctype="multipart/form-data" class="fm">
            @csrf
            <div><label for="hwA">تکلیف</label><select class="fld" id="hwA" name="homework_id" required>
                @foreach($homeworks as $homework)<option value="{{ $homework->id }}">{{ $homework->title }}</option>@endforeach
            </select></div>
            <div><label for="hwF">فایل (عکس، PDF یا ورد)</label><input class="fld" id="hwF" name="files[]" type="file" accept="image/*,.pdf,.doc,.docx" multiple></div>
            <div class="w"><label for="hwT">یا پاسخ متنی</label><textarea class="fld" id="hwT" name="answer" maxlength="12000"></textarea></div>
            <div class="w"><button class="btn" type="submit" @disabled($homeworks->isEmpty())>ارسال تکلیف</button></div>
        </form>
    </div>
    @endcan
</section>
