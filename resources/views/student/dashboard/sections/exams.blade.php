<section class="page" id="s-exams"><div class="card"><div class="ch"><h2>آزمون‌ها</h2><a class="btn s" href="{{ route('student.exams.index') }}">فهرست آزمون‌ها</a></div>
    <div class="tw"><table><thead><tr><th>آزمون</th><th>دوره یا کلاس</th><th>وضعیت</th><th>نمره</th></tr></thead><tbody>
    @forelse($results as $result)<tr><td>{{ $result->exam?->title ?? 'آزمون' }}</td><td>{{ $result->exam?->course?->title ?? $result->exam?->classroom?->name ?? '—' }}</td><td><span class="tag d">نتیجه ثبت‌شده</span></td><td>{{ $result->score === null ? 'در انتظار نمره' : $faNumber($result->score, 2).' / '.$faNumber($result->max_score, 2) }}</td></tr>
    @empty<tr><td colspan="4" class="empty">نتیجه‌ای برای نمایش ثبت نشده است.</td></tr>@endforelse
    </tbody></table></div><p class="info" style="margin-top:10px">آزمون‌های فعال و شروع آزمون از فهرست رسمی آزمون‌ها انجام می‌شود.</p>
</div></section>
