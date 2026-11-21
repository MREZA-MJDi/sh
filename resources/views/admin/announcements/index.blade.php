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
            <thead><tr><th>عنوان و متن</th><th>مخاطب</th><th>زمان انتشار / اعتبار</th><th>وضعیت</th><th>عملیات</th></tr></thead>
            <tbody>
                @forelse($announcements as $announcement)
                    @php
                        $fill = [
                            'title' => $announcement->title,
                            'body' => $announcement->body,
                            'audience' => $announcement->audience,
                            'status' => $announcement->status,
                            'starts_at' => $announcement->starts_at?->format('Y-m-d\\TH:i'),
                            'ends_at' => $announcement->ends_at?->format('Y-m-d\\TH:i'),
                            'priority' => $announcement->priority ?? 'normal',
                            'action_url' => $announcement->action_url,
                            'action_label' => $announcement->action_label,
                        ];
                        $status = $statuses[$announcement->status] ?? ['mute', $announcement->status];
                    @endphp
                    <tr>
                        <td><b>{{ $announcement->title }}</b><small>{{ \Illuminate\Support\Str::limit(strip_tags($announcement->body), 140) }}</small></td>
                        <td>{{ $audiences[$announcement->audience] ?? $announcement->audience }}</td>
                        <td>{{ ($announcement->published_at ? \App\Support\PersianDate::format($announcement->published_at, 'Y/m/d H:i') : '—') }}</td>
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
    <x-admin.select label="وضعیت انتشار" name="status" :options="['draft' => 'پیش‌نویس', 'published' => 'انتشار / زمان‌بندی']" required/>
    <x-admin.select label="اولویت نمایش" name="priority" :options="['normal' => 'عادی', 'important' => 'مهم', 'urgent' => 'فوری']" required/>
    <label class="adm-field"><span>شروع نمایش (اختیاری)</span><input type="datetime-local" name="starts_at"></label>
    <label class="adm-field"><span>پایان نمایش (اختیاری)</span><input type="datetime-local" name="ends_at"></label>
    <label class="adm-field wide"><span>آدرس دکمه (اختیاری) — فقط HTTPS</span><input type="url" name="action_url" placeholder="https://example.com"></label>
    <x-admin.input label="متن دکمه" name="action_label" wide/>
    <p class="adm-muted wide">اعلان «همه کاربران» در صفحات عمومی و همه داشبوردها نمایش داده می‌شود؛ سایر مخاطبان فقط در داشبورد نقش مربوطه. تاریخ پایان اختیاری است. پیامک ارسال نمی‌شود.</p>
</x-admin.modal>
@endpush
