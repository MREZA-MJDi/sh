@extends('admin.layouts.app')

@section('page-title', 'اطلاع‌رسانی')
@section('page-description', 'اطلاعیه‌های آکادمی و مخاطبان هر پیام')

@section('content')
    <div class="panel-page-head">
        <div><h2>اطلاعیه‌ها</h2><p>{{ $announcements->total() }} اطلاعیه ثبت‌شده</p></div>
        @can('announcements.manage')
            <button class="panel-btn" type="button" data-admin-drawer-open="announcement-create" data-drawer-title="اطلاعیه جدید">＋ اطلاعیه جدید</button>
        @endcan
    </div>

    <section class="panel-card">
        <div class="panel-card-title"><div><h3>اطلاعیه‌های قبلی</h3><p>متن، مخاطب، تاریخ و وضعیت انتشار</p></div></div>
        <div class="panel-table-wrap">
            <table class="panel-table" data-searchable-table>
                <thead><tr><th>عنوان</th><th>مخاطب</th><th>ثبت‌کننده</th><th>انتشار</th><th>وضعیت</th>@can('announcements.manage')<th>عملیات</th>@endcan</tr></thead>
                <tbody>
                @forelse ($announcements as $announcement)
                    <tr>
                        <td><strong>{{ $announcement->title }}</strong><details class="panel-details mt-1"><summary>متن اطلاعیه</summary><div class="panel-details-content whitespace-normal">{{ $announcement->body }}</div></details></td>
                        <td>{{ ['all'=>'همه','students'=>'دانش‌آموزان','parents'=>'اولیا','teachers'=>'مدرسان'][$announcement->audience] ?? $announcement->audience }}</td>
                        <td>{{ $announcement->creator->name ?? '—' }}</td>
                        <td>{{ $announcement->published_at?->format('Y-m-d H:i') ?? '—' }}</td>
                        <td><x-admin::badge :variant="$announcement->status === 'published' ? 'success' : 'warning'">{{ ['draft'=>'پیش‌نویس','published'=>'منتشرشده','archived'=>'بایگانی'][$announcement->status] ?? $announcement->status }}</x-admin.badge></td>
                        @can('announcements.manage')
                            <td class="panel-table-actions">
                                <form method="POST" action="{{ route('admin.announcements.status', $announcement) }}" class="panel-inline-form">
                                    @csrf @method('PATCH')
                                    <select class="panel-select" name="status" aria-label="وضعیت اطلاعیه">@foreach (['draft'=>'پیش‌نویس','published'=>'منتشرشده','archived'=>'بایگانی'] as $key=>$label)<option value="{{ $key }}" @selected($announcement->status === $key)>{{ $label }}</option>@endforeach</select>
                                    <button class="panel-btn panel-btn-secondary panel-btn-sm" type="submit">ثبت</button>
                                </form>
                                <form method="POST" action="{{ route('admin.announcements.destroy', $announcement) }}" data-confirm="اطلاعیه حذف شود؟">@csrf @method('DELETE')<button class="panel-btn panel-btn-danger panel-btn-sm" type="submit">حذف</button></form>
                            </td>
                        @endcan
                    </tr>
                @empty
                    <tr><td colspan="6" class="panel-empty">اطلاعیه‌ای برای نمایش وجود ندارد.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="panel-pagination"><span>{{ $announcements->count() }} از {{ $announcements->total() }} اطلاعیه</span>{{ $announcements->links() }}</div>
    </section>

    @can('announcements.manage')
        <x-admin::drawer-form id="announcement-create" title="اطلاعیه جدید" :action="route('admin.announcements.store')" submit-label="ثبت اطلاعیه">
            <div class="panel-field"><label>عنوان</label><input class="panel-input" name="title" required maxlength="180"></div>
            <div class="panel-field"><label>مخاطبان</label><select class="panel-select" name="audience"><option value="all">همه کاربران</option><option value="students">دانش‌آموزان</option><option value="parents">اولیا</option><option value="teachers">مدرسان</option></select></div>
            <div class="panel-field"><label>وضعیت اولیه</label><select class="panel-select" name="status"><option value="draft">ذخیره پیش‌نویس</option><option value="published">انتشار فوری</option></select></div>
            <div class="panel-field"><label>متن اطلاعیه</label><textarea class="panel-textarea" name="body" required maxlength="20000"></textarea></div>
        </x-admin.drawer-form>
    @endcan
@endsection
