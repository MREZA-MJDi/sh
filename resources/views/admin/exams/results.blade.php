@extends('layouts.panel')

@section('page-title', 'نتایج آزمون')
@section('page-description', 'کارنامه‌های ثبت‌شده برای آزمون انتخاب‌شده')

@section('content')
    <section class="panel-card mb-4">
        <div class="panel-card-title">
            <div>
                <h3>{{ $exam->title }}</h3>
                <p>{{ $exam->provider === 'quiz24' ? 'منبع: Quiz24' : 'منبع: آزمون داخلی' }} · {{ $exam->results_count ?? $results->total() }} نتیجه</p>
            </div>
            <a class="panel-btn panel-btn-secondary" href="{{ route('admin.exams.index') }}">بازگشت به آزمون‌ها</a>
        </div>
        <div class="panel-muted">
            @if ($exam->provider === 'quiz24')
                نتایج Quiz24 فقط پس از دریافت پاسخ معتبر API وارد این فهرست می‌شوند. درخواست همگام‌سازی به‌تنهایی به معنی دریافت نتیجه نیست.
            @else
                در این صفحه فقط نتیجه‌هایی نمایش داده می‌شوند که در سامانه ثبت شده‌اند.
            @endif
        </div>
    </section>

    <section class="panel-card">
        <div class="panel-table-wrap">
            <table class="panel-table" data-searchable-table>
                <thead>
                    <tr><th>دانش‌آموز</th><th>نمره</th><th>از</th><th>درصد</th><th>آخرین همگام‌سازی</th></tr>
                </thead>
                <tbody>
                    @forelse ($results as $result)
                        <tr>
                            <td>{{ $result->student->name ?? 'حساب حذف‌شده' }}</td>
                            <td>{{ $result->score ?? '—' }}</td>
                            <td>{{ $result->max_score }}</td>
                            <td>{{ $result->max_score > 0 && $result->score !== null ? number_format(((float) $result->score / (float) $result->max_score) * 100, 1).'٪' : '—' }}</td>
                            <td>{{ $result->synced_at?->format('Y-m-d H:i') ?? 'ثبت داخلی' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="panel-empty">هنوز نتیجه‌ای برای این آزمون ثبت نشده است.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="panel-pagination"><span>{{ $results->count() }} از {{ $results->total() }} نتیجه</span>{{ $results->links() }}</div>
    </section>
@endsection
