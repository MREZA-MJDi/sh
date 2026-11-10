@extends('layouts.panel')

@section('page-title', 'اطلاعیه‌ها')
@section('page-description', 'انتشار اطلاعیه برای همه کاربران یا گروه مشخص')

@section('content')
    <section class="panel-card mb-4">
        <div class="panel-card-title"><div><h3>ایجاد اطلاعیه</h3><p>اطلاعیه پیش‌نویس تا زمان انتشار برای کاربران نمایش داده نمی‌شود.</p></div></div>
        <form method="POST" action="{{ route('admin.announcements.store') }}">
            @csrf
            <div class="panel-form-grid panel-form-grid-3">
                <div class="panel-field"><label>عنوان</label><input class="panel-input" name="title" value="{{ old('title') }}" required maxlength="180"></div>
                <div class="panel-field"><label>گیرندگان</label><select class="panel-select" name="audience"><option value="all">همه کاربران</option><option value="students">دانش‌آموزان</option><option value="parents">اولیا</option><option value="teachers">مدرسان</option></select></div>
                <div class="panel-field"><label>وضعیت اولیه</label><select class="panel-select" name="status"><option value="draft">ذخیره پیش‌نویس</option><option value="published">انتشار فوری</option></select></div>
            </div>
            <div class="panel-field mt-3"><label>متن اطلاعیه</label><textarea class="panel-textarea" name="body" required maxlength="20000">{{ old('body') }}</textarea></div>
            <div class="panel-form-actions"><button class="panel-btn" type="submit">ذخیره اطلاعیه</button></div>
        </form>
    </section>

    <section class="panel-card">
        <div class="panel-card-title"><div><h3>اطلاعیه‌های قبلی</h3><p>وضعیت انتشار و مخاطب هر اطلاعیه</p></div><span class="panel-badge">{{ $announcements->total() }} اطلاعیه</span></div>
        <div class="panel-table-wrap">
            <table class="panel-table" data-searchable-table>
                <thead><tr><th>عنوان</th><th>مخاطب</th><th>ثبت‌کننده</th><th>تاریخ انتشار</th><th>وضعیت</th><th>عملیات</th></tr></thead>
                <tbody>
                @forelse ($announcements as $announcement)
                    <tr>
                        <td><strong>{{ $announcement->title }}</strong><details class="panel-details mt-1"><summary>متن اطلاعیه</summary><div class="panel-details-content whitespace-normal">{{ $announcement->body }}</div></details></td>
                        <td>{{ ['all'=>'همه','students'=>'دانش‌آموزان','parents'=>'اولیا','teachers'=>'مدرسان'][$announcement->audience] ?? $announcement->audience }}</td>
                        <td>{{ $announcement->creator->name ?? '—' }}</td>
                        <td>{{ $announcement->published_at?->format('Y-m-d H:i') ?? '—' }}</td>
                        <td><span class="panel-badge {{ $announcement->status==='published' ? 'panel-badge-success' : 'panel-badge-warning' }}">{{ ['draft'=>'پیش‌نویس','published'=>'منتشرشده','archived'=>'بایگانی'][$announcement->status] ?? $announcement->status }}</span></td>
                        <td class="panel-table-actions">
                            <form method="POST" action="{{ route('admin.announcements.status', $announcement) }}" class="panel-inline-form">
                                @csrf @method('PATCH')
                                <select class="panel-select" name="status" aria-label="وضعیت اطلاعیه">
                                    @foreach (['draft'=>'پیش‌نویس','published'=>'منتشرشده','archived'=>'بایگانی'] as $key=>$value)
                                        <option value="{{ $key }}" @selected($announcement->status===$key)>{{ $value }}</option>
                                    @endforeach
                                </select>
                                <button class="panel-btn panel-btn-secondary panel-btn-sm" type="submit">ثبت</button>
                            </form>
                            <form method="POST" action="{{ route('admin.announcements.destroy', $announcement) }}" data-confirm="اطلاعیه حذف شود؟">
                                @csrf @method('DELETE')
                                <button class="panel-btn panel-btn-danger panel-btn-sm" type="submit">حذف</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="panel-empty">اطلاعیه‌ای ثبت نشده است.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="panel-pagination"><span>{{ $announcements->count() }} از {{ $announcements->total() }} اطلاعیه</span>{{ $announcements->links() }}</div>
    </section>
@endsection
