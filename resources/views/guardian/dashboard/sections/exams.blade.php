<section class="page" id="p-exams"><div class="card"><div class="ch"><h2>نتایج آزمون‌ها</h2><span class="tag d">{{ $faNumber($results->count()) }} نتیجه</span></div>
    <div class="tw"><table><thead><tr><th>آزمون</th><th>تاریخ</th><th>نمره</th><th>بازخورد</th></tr></thead><tbody>
    @forelse($results as $result)<tr><td>{{ $result->exam?->title ?? 'آزمون' }}</td><td>{{ \App\Support\PersianDate::format($result->synced_at ?? $result->created_at, 'Y/m/d') }}</td><td>{{ $result->score === null ? 'در انتظار نمره' : $faNumber($result->score,2).' / '.$faNumber($result->max_score,2) }}</td><td>{{ $result->feedback ?? '—' }}</td></tr>
    @empty<tr><td colspan="4" class="empty">نتیجه آزمونی برای نمایش وجود ندارد.</td></tr>@endforelse
    </tbody></table></div>
</div></section>
