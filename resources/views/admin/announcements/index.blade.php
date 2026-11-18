@extends('admin.layouts.app')
@section('title', 'اعلان‌ها')

@section('content')
@php
    $audiences = ['all' => 'همه کاربران', 'students' => 'دانش‌آموزان', 'parents' => 'اولیا', 'teachers' => 'مدرسان'];
    $statuses = ['published' => ['ok', 'منتشرشده'], 'draft' => ['warn', 'پیش‌نویس']];
@endphp

<x-admin.page-head tone="comm" icon="bell" title="اعلان‌ها" desc="انتشار اطلاعیه واقعی در پنل عمومی گروه‌های کاربری">
    <x-admin.btn icon="plus" data-modal-open="announcement-modal">اعلان جدید</x-admin.btn>
</x-admin.page-head>

<x-admin.panel title="اطلاعیه‌های ثبت‌شده" flush data-tone="comm">
    <div class="adm-table-wrap">
        <table class="adm-table">
            <thead><tr><th>عنوان و متن</th><th>مخاطب</th><th>زمان انتشار</th><th>وضعیت</th><th>عملیات</th></tr></thead>
            <tbody>
                @forelse($announcements as $announcement)
                    @php
                        $fill = [
                            'title' => $announcement->title,
                            'body' => $announcement->body,
                            'audience' => $announcement->audience,
                            'status' => $announcement->status,
                        ];
                        $status = $statuses[$announcement->status] ?? ['mute', $announcement->status];
                    @endphp
                    <tr>
                        <td><b>{{ $announcement->title }}</b><small>{{ \Illuminate\Support\Str::limit(strip_tags($announcement->body), 140) }}</small></td>
                        <td>{{ $audiences[$announcement->audience] ?? $announcement->audience }}</td>
                        <td>{{ $announcement->published_at?->format('Y/m/d H:i') ?? '—' }}</td>
                        <td><x-admin.badge :tone="$status[0]">{{ $status[1] }}</x-admin.badge></td>
                        <td>
                            <div class="adm-actions">
                                <x-admin.btn size="sm" variant="soft" icon="edit" data-modal-open="announcement-modal"
                                    data-title="ویرایش اعلان"
                                    :data-fill="json_encode($fill, JSON_UNESCAPED_UNICODE)"
                                    :data-action="route('admin.announcements.update', $announcement)"
                                    data-method="PATCH">ویرایش</x-admin.btn>
                                <form method="POST" action="{{ route('admin.announcements.destroy', $announcement) }}"
                                      onsubmit="return confirm('این اعلان حذف شود؟')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="adm-btn adm-btn-line adm-btn-sm" type="submit">حذف</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5">هنوز اطلاعیه‌ای ثبت نشده است.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <x-slot:footer>{{ $announcements->links() }}</x-slot:footer>
</x-admin.panel>
@endsection

@push('modals')
<x-admin.modal :demo="false" id="announcement-modal" title="اعلان جدید" size="lg"
    :action="route('admin.announcements.store')">
    <x-admin.legend title="متن اطلاعیه"/>
    <x-admin.input label="عنوان" name="title" wide required/>
    <x-admin.textarea label="متن اعلان" name="body" rows="5" wide required/>
    <x-admin.select label="مخاطب" name="audience" :options="$audiences" required/>
    <x-admin.select label="وضعیت انتشار" name="status" :options="['draft' => 'پیش‌نویس', 'published' => 'انتشار فوری']" required/>
    <p class="adm-muted wide">اعلان منتشرشده در داشبورد گروه انتخاب‌شده نمایش داده می‌شود. ارسال پیامک از این فرم انجام نمی‌شود.</p>
</x-admin.modal>
@endpush
