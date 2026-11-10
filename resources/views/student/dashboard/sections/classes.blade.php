<section class="page" id="s-classes"><div class="card"><div class="ch"><h2>کلاس‌های من</h2><span class="tag">فقط کلاس‌های ثبت‌نام‌شده</span></div>
    @forelse($classrooms as $classroom)
        <div class="it"><div class="th"></div><div><b>{{ $classroom->name }}</b><span>{{ $classroom->course?->title ?? 'کلاس مستقل' }} · استاد {{ $classroom->teacher?->name ?? 'تعیین نشده' }}</span></div></div>
    @empty<div class="empty">هنوز کلاسی برای این حساب ثبت نشده است.</div>@endforelse
    <p class="info" style="margin-top:10px">لینک ورود به کلاس و ثبت حضور، فقط در صورت اتصال لینک معتبر کلاس نمایش داده می‌شود.</p>
</div></section>
