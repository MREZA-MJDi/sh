@extends('layouts.panel')

@section('page-title', 'درخواست‌های مشاوره')
@section('page-description', 'پیگیری درخواست‌های مشاوره؛ جلسات زمان‌بندی‌شده در کارتابل مستقل جلسات اولیا مدیریت می‌شوند.')

@section('content')
    <section class="panel-card">
        <div class="panel-card-title"><div><h3>کارتابل درخواست‌ها</h3><p>وضعیت و زمان‌بندی هر درخواست از همین بخش مدیریت می‌شود.</p></div><span class="panel-badge">{{ $consultations->total() }} درخواست</span></div>
        <div class="panel-table-wrap">
            <table class="panel-table" data-searchable-table>
                <thead><tr><th>درخواست‌کننده</th><th>دانش‌آموز</th><th>نوع</th><th>زمان پیشنهادی</th><th>وضعیت</th><th>مدیریت درخواست</th></tr></thead>
                <tbody>
                @forelse ($consultations as $consultation)
                    <tr>
                        <td><strong>{{ $consultation->requester->name ?? 'کاربر حذف‌شده' }}</strong><div class="panel-muted">{{ $consultation->requester->phone ?? $consultation->requester->email ?? '' }}</div></td>
                        <td>{{ $consultation->student->name ?? '—' }}</td>
                        <td>{{ ['phone'=>'تلفنی','in_person'=>'حضوری','ticket'=>'تیکت'][$consultation->type] ?? $consultation->type }}</td>
                        <td>{{ $consultation->desired_at?->format('Y-m-d H:i') ?? '—' }}</td>
                        <td>
                            @php($consultationStatus = ['approved' => 'scheduled', 'completed' => 'done'][$consultation->status] ?? $consultation->status)
                            <span class="panel-badge {{ in_array($consultationStatus, ['scheduled', 'done'], true) ? 'panel-badge-success' : ($consultationStatus === 'pending' ? 'panel-badge-warning' : '') }}">
                                {{ ['pending'=>'در انتظار','scheduled'=>'هماهنگ‌شده','done'=>'انجام‌شده','cancelled'=>'لغوشده'][$consultationStatus] ?? $consultationStatus }}
                            </span>
                        </td>
                        <td>
                            <details class="panel-details">
                                <summary>بررسی / ویرایش</summary>
                                <div class="panel-details-content">
                                    <form method="POST" action="{{ route('admin.consultations.update', $consultation) }}" class="grid gap-2">
                                        @csrf @method('PATCH')
                                        <div class="panel-field"><label>وضعیت</label><select class="panel-select" name="status">@foreach (['pending'=>'در انتظار','scheduled'=>'هماهنگ‌شده','done'=>'انجام‌شده','cancelled'=>'لغو'] as $key=>$value)<option value="{{ $key }}" @selected((['approved' => 'scheduled', 'completed' => 'done'][$consultation->status] ?? $consultation->status)===$key)>{{ $value }}</option>@endforeach</select></div>
                                        <div class="panel-field"><label>زمان نهایی جلسه</label><input class="panel-input" type="datetime-local" name="scheduled_at" value="{{ $consultation->scheduled_at?->format('Y-m-d\TH:i') }}"></div>
                                        <div class="panel-field"><label>یادداشت پیگیری</label><textarea class="panel-textarea" name="notes">{{ $consultation->notes }}</textarea></div>
                                        <button class="panel-btn panel-btn-sm" type="submit">ذخیره پیگیری</button>
                                    </form>
                                </div>
                            </details>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="panel-empty">درخواستی ثبت نشده است.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="panel-pagination"><span>{{ $consultations->count() }} از {{ $consultations->total() }} درخواست</span>{{ $consultations->links() }}</div>
    </section>
@endsection
