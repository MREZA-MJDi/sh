@extends('admin.layouts.app')

@section('page-title', 'جلسه‌های ' . $classroom->name)
@section('page-description', 'زمان‌بندی جلسه‌های زنده، لینک ورود اسکای‌روم و وضعیت هر جلسه')

@php
    $canManage = auth()->user()->hasAnyPermission(['classes.manage', 'classes.manage.assigned']);
    $statusLabels = ['scheduled' => 'برگزار می‌شود', 'completed' => 'برگزار شد', 'cancelled' => 'لغو شد'];
    $statusVariant = ['scheduled' => 'info', 'completed' => 'success', 'cancelled' => 'danger'];
    $typeLabels = ['online' => 'آنلاین (اسکای‌روم)', 'onsite' => 'حضوری', 'legacy' => 'ثبت قدیمی'];
@endphp

@section('content')
    <div class="panel-page-head" data-tone="learning">
        <div>
            <h2>جلسه‌های کلاس {{ $classroom->name }}</h2>
            <p>{{ $classroom->course->title ?? 'بدون دوره' }} · مدرس: {{ $classroom->teacher->name ?? '—' }}</p>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
            <a class="panel-btn panel-btn-secondary" href="{{ route('admin.classes.index') }}">← همه کلاس‌ها</a>
            @if ($canManage)
                <button class="panel-btn" type="button" data-admin-drawer-open="session-create" data-drawer-title="جلسه جدید">＋ جلسه جدید</button>
            @endif
        </div>
    </div>

    <section class="panel-grid panel-grid-3 mb-4">
        <x-admin::stat label="کل جلسه‌ها" :value="number_format($sessions->total())" note="ثبت‌شده برای این کلاس" tone="learning" icon="▣"/>
        <x-admin::stat label="جلسه بعدی" :value="$nextSession ? $nextSession->starts_at->format('Y/m/d H:i') : '—'" :note="$nextSession?->title ?? 'جلسه‌ی زمان‌بندی‌شده‌ای نیست'" tone="presence" icon="◷"/>
        <x-admin::stat label="لینک اسکای‌روم کلاس" :value="$classroom->meeting_url ? 'ثبت شده' : 'ثبت نشده'" :note="$classroom->meeting_url ? 'برای جلسه‌های آنلاین پیش‌فرض است' : 'در ویرایش کلاس وارد کنید'" tone="system" icon="↗"/>
    </section>

    <section class="panel-card" data-tone="learning">
        <div class="panel-card-title"><div><h3>فهرست جلسه‌ها</h3><p>جدیدترین جلسه بالاست. جلسه‌ای که حضور یا کلیک ورود دارد حذف نمی‌شود؛ آن را لغو کنید.</p></div></div>
        <div class="panel-table-wrap">
            <table class="panel-table">
                <thead><tr><th>عنوان</th><th>نوع</th><th>زمان</th><th>وضعیت</th><th>حضور ثبت‌شده</th><th>لینک ورود</th>@if ($canManage)<th>عملیات</th>@endif</tr></thead>
                <tbody>
                @forelse ($sessions as $session)
                    <tr>
                        <td><strong>{{ $session->title }}</strong></td>
                        <td>{{ $typeLabels[$session->session_type] ?? $session->session_type }}</td>
                        <td>{{ $session->starts_at->format('Y/m/d H:i') }}@if ($session->ends_at)<div class="panel-muted">تا {{ $session->ends_at->format('H:i') }}</div>@endif</td>
                        <td><x-admin::badge :variant="$statusVariant[$session->status] ?? 'info'">{{ $statusLabels[$session->status] ?? $session->status }}</x-admin::badge></td>
                        <td>{{ number_format($session->attendances_count) }} نفر</td>
                        <td>
                            @if ($session->meeting_url)
                                <a href="{{ $session->meeting_url }}" target="_blank" rel="noopener noreferrer" dir="ltr">ورود ↗</a>
                            @else
                                <span class="panel-muted">—</span>
                            @endif
                        </td>
                        @if ($canManage)
                            <td>
                                <div style="display:flex;gap:6px;flex-wrap:wrap">
                                    <button class="panel-btn panel-btn-secondary panel-btn-sm" type="button" data-admin-drawer-open="session-edit-{{ $session->id }}" data-drawer-title="ویرایش جلسه">ویرایش</button>
                                    <form method="POST" action="{{ route('admin.classes.sessions.destroy', $session->id) }}" data-confirm="این جلسه حذف شود؟ این کار قابل بازگشت نیست.">
                                        @csrf @method('DELETE')
                                        <button class="panel-btn panel-btn-danger panel-btn-sm" type="submit">حذف</button>
                                    </form>
                                </div>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr><td colspan="7" class="panel-empty">هنوز جلسه‌ای ثبت نشده است. با دکمه «جلسه جدید» اولین جلسه را بسازید.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="panel-pagination"><span>{{ $sessions->count() }} از {{ $sessions->total() }} جلسه</span>{{ $sessions->links() }}</div>
    </section>

    @if ($canManage)
        <x-admin::drawer-form id="session-create" title="جلسه جدید" :action="route('admin.classes.sessions.store', $classroom->id)" submit-label="ثبت جلسه">
            @include('admin.classes._session_fields', ['session' => null, 'classroom' => $classroom])
        </x-admin::drawer-form>

        @foreach ($sessions as $session)
            <x-admin::drawer-form :id="'session-edit-'.$session->id" title="ویرایش جلسه" :action="route('admin.classes.sessions.update', $session->id)" submit-label="ذخیره تغییرات">
                @method('PATCH')
                @include('admin.classes._session_fields', ['session' => $session, 'classroom' => $classroom])
            </x-admin::drawer-form>
        @endforeach
    @endif
@endsection