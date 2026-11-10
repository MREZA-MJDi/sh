<section class="page" id="p-progress"><div class="card"><div class="ch"><h2>پیشرفت دوره‌ها و کلاس‌ها · {{ $selected_child?->name ?? 'فرزند' }}</h2></div>
    @forelse($course_progress as $courseProgress)<div class="it"><div class="th"></div><div><b>{{ $courseProgress->title }}</b><div class="prog"><i style="width:{{ max(0,min(100,(int)$courseProgress->progress_percent)) }}%"></i></div><span>{{ $faNumber($courseProgress->progress_percent) }}٪ · {{ $faNumber($courseProgress->completed_lessons) }} از {{ $faNumber($courseProgress->total_lessons) }} جلسه</span></div></div>
    @empty<div class="empty">اطلاعات پیشرفت دوره‌ای برای این فرزند ثبت نشده است.</div>@endforelse
    <div class="ch" style="margin-top:14px"><h2>کلاس‌های ثبت‌نام‌شده</h2></div>
    @forelse($classrooms as $classroom)<div class="it"><div class="th c"></div><div><b>{{ $classroom->name }}</b><span>{{ $classroom->course?->title ?? 'کلاس مستقل' }} · استاد {{ $classroom->teacher?->name ?? 'تعیین نشده' }}</span></div></div>
    @empty<div class="empty">برای این فرزند کلاسی ثبت نشده است.</div>@endforelse
</div></section>
