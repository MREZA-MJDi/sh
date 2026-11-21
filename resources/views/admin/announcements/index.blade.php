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
            <thead><tr><th>عنوان و متن</th><th>مخاطب</th><th>زمان انتشار / اعتبار</th><th>نوع نمایش</th><th>وضعیت</th><th>عملیات</th></tr></thead>
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
                            'display_as_ticket' => (bool) $announcement->display_as_ticket,
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
                    <tr><td colspan="6">هنوز اطلاعیه‌ای ثبت نشده است.</td></tr>
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
    <x-admin.legend title="محتوای اطلاعیه"/>
    <p class="adm-muted wide">اطلاعات این فرم در سایت و داشبورد مخاطبان نمایش داده می‌شود. برای کارت صفحه اصلی، گزینه نمایش هولوگرافیک را فعال کن.</p>
    <x-admin.input label="عنوان" name="title" wide required/>
    <x-admin.textarea label="متن اعلان" name="body" rows="5" wide required/>
    <x-admin.select label="مخاطب" name="audience" :options="$audiences" required/>
    <x-admin.select label="وضعیت انتشار" name="status" :options="['draft' => 'پیش‌نویس', 'published' => 'انتشار / زمان‌بندی']" required/>
    <x-admin.select label="اولویت نمایش" name="priority" :options="['normal' => 'عادی', 'important' => 'مهم', 'urgent' => 'فوری']" required/>
    <x-admin.legend title="زمان‌بندی و دکمه اقدام"/>
    <label class="adm-field"><span>شروع نمایش (اختیاری)</span><input type="datetime-local" name="starts_at"></label>
    <label class="adm-field"><span>پایان نمایش (اختیاری)</span><input type="datetime-local" name="ends_at"></label>
    <label class="adm-field wide"><span>آدرس دکمه (اختیاری)</span><input type="text" name="action_url" inputmode="url" placeholder="/courses یا https://example.com"><small class="adm-muted">مسیر داخلی سایت مانند /courses یا آدرس امن HTTPS وارد کن.</small></label>
    <x-admin.input label="متن دکمه" name="action_label" wide/>
    <x-admin.legend title="نحوه نمایش در صفحه اصلی"/>
    <input type="hidden" name="display_as_ticket" value="0">
    <label class="adm-field wide" style="display:flex;align-items:flex-start;gap:10px;padding:14px;border:1px solid var(--adm-border);border-radius:12px"><input type="checkbox" name="display_as_ticket" value="1" style="width:18px;height:18px;margin-top:3px"><span><b>نمایش به‌شکل کارت پذیرش هولوگرافیک</b><small class="adm-muted" style="display:block">این اعلان به‌جای سکشن مراحل یادگیری در صفحه اصلی قرار می‌گیرد و فقط برای مخاطب «همه کاربران» قابل انتخاب است.</small></span></label>
    <div class="wide" style="padding:14px;border-radius:12px;background:var(--adm-soft, #f5f6fb)"><b>پیش‌نمایش رفتار کارت</b><p class="adm-muted" style="margin:6px 0 0">عنوان و متن از همین فرم خوانده می‌شود. تاریخ پایان، شمارش معکوس را فعال می‌کند. بدون تاریخ پایان، کارت بدون شمارنده نمایش داده می‌شود. تخفیف و ظرفیت ساختگی نمایش داده نمی‌شود.</p></div>
    <p class="adm-muted wide">اعلان «همه کاربران» در صفحات عمومی و همه داشبوردها نمایش داده می‌شود؛ سایر مخاطبان فقط در داشبورد نقش مربوطه. تاریخ پایان اختیاری است. پیامک ارسال نمی‌شود.</p>
</x-admin.modal>
@endpush
