@extends('admin.layouts.app')
@section('title', 'آزمون‌ها')

@section('content')
@php
  $classOptions = $classrooms->pluck('name', 'id')->all();
  $courseOptions = $courses->pluck('title', 'id')->all();
  $providerOptions = $teacherView
      ? ['internal' => 'آزمون داخلی شیخان']
      : ['internal' => 'آزمون داخلی شیخان', 'quiz24' => 'Quiz24 (اتصال پس از دریافت قرارداد)'];
  $statusOptions = [
      'draft' => 'پیش‌نویس',
      'scheduled' => 'زمان‌بندی‌شده',
      'published' => 'منتشرشده',
      'closed' => 'بسته‌شده',
  ];
  $statusTones = ['draft' => 'warn', 'scheduled' => 'info', 'published' => 'ok', 'closed' => 'muted'];
  $statusLabels = ['draft' => 'پیش‌نویس', 'scheduled' => 'زمان‌بندی‌شده', 'published' => 'منتشرشده', 'closed' => 'بسته‌شده'];
@endphp

<x-admin.page-head tone="track" icon="exam" title="آزمون‌ها" desc="ساخت آزمون داخلی، مدیریت اتصال Quiz24، سؤال‌ها و نتایج">
  <x-admin.btn icon="plus" data-modal-open="exam-modal">آزمون جدید</x-admin.btn>
</x-admin.page-head>

<section class="adm-exam-guide" data-tone="track">
  <div><span>مسیر مدیریت آزمون</span><b>آزمون را بساز، زمان‌بندی کن، سؤال‌ها و نتایج را مدیریت کن.</b><small>آزمون داخلی در شیخان اجرا می‌شود؛ اتصال Quiz24 تا زمان فعال‌شدن قرارداد مستقل باقی می‌ماند.</small></div>
  @if(auth()->user()->hasPermissionTo('integrations.manage'))
    <x-admin.btn size="sm" variant="line" icon="link" :href="route('admin.quiz24.index')">وضعیت اتصال Quiz24</x-admin.btn>
  @endif
</section>

<div class="adm-stats">
  <x-admin.stat tone="track" icon="exam" label="آزمون‌های فهرست" :value="number_format($stats['total'])" hint="بر اساس دسترسی نقش شما"/>
  <x-admin.stat tone="track" icon="clock" label="در جریان / پیش‌رو" :value="number_format($stats['upcoming'])" hint="در کل آزمون‌های فیلترشده"/>
  <x-admin.stat tone="track" icon="link" label="آزمون‌های Quiz24" :value="number_format($stats['quiz24'])" hint="وضعیت اتصال جداگانه"/>
  <x-admin.stat tone="track" icon="edit" label="پیش‌نویس‌ها" :value="number_format($stats['drafts'])" hint="نیازمند تکمیل و انتشار"/>
</div>

<x-admin.panel title="تقویم آزمون‌ها" desc="بازه شروع آزمون‌ها را از تقویم انتخاب کنید." data-tone="track">
  <form method="GET" action="{{ url()->current() }}" class="adm-toolbar">
    <label class="adm-label">از تاریخ <input class="adm-input" type="date" name="from" value="{{ $filters['from'] ?? '' }}"></label>
    <label class="adm-label">تا تاریخ <input class="adm-input" type="date" name="to" value="{{ $filters['to'] ?? '' }}"></label>
    <div class="adm-toolbar-end"><x-admin.btn type="submit" size="sm">اعمال فیلتر</x-admin.btn><x-admin.btn :href="url()->current()" variant="line" size="sm">پاک‌کردن</x-admin.btn></div>
  </form>
</x-admin.panel>
<x-admin.panel flush data-filter data-tone="track">
  <x-admin.toolbar placeholder="جستجوی عنوان آزمون…">
    <x-admin.filter-select key="provider" placeholder="همه سرویس‌ها" :options="['internal' => 'داخلی شیخان', 'quiz24' => 'Quiz24']"/>
    <div class="adm-toolbar-end"><x-admin.seg :options="['draft' => 'پیش‌نویس', 'scheduled' => 'زمان‌بندی', 'published' => 'منتشرشده', 'closed' => 'بسته‌شده']"/></div>
  </x-admin.toolbar>

  <div class="adm-grid adm-cols-2" data-filter-list>
    @forelse($exams as $exam)
      @php
        $scopeName = $exam->classroom?->name ?? $exam->course?->title ?? 'بدون تخصیص';
        $fill = [
          'title' => $exam->title,
          'description' => $exam->description,
          'classroom_id' => (string) ($exam->classroom_id ?? ''),
          'course_id' => (string) ($exam->course_id ?? ''),
          'provider' => $exam->provider,
          'external_id' => $exam->external_id,
          'starts_at' => $exam->starts_at?->format('Y-m-d\\TH:i'),
          'duration_hours' => max(1, min(4, (int) ceil(((int) $exam->duration_minutes) / 60))),
          'attempts_allowed' => $exam->attempts_allowed,
          'show_to_guardians' => (bool) $exam->show_to_guardians,
          'status' => $exam->status,
        ];
      @endphp
      <article class="adm-card" data-name="{{ $exam->title.' '.$scopeName }}" data-state="{{ $exam->status }}" data-provider="{{ $exam->provider }}">
        <div class="adm-card-body">
          <div class="adm-card-title">
            <div><h3>{{ $exam->title }}</h3><p>{{ $scopeName }}</p></div>
            <x-admin.badge :tone="$statusTones[$exam->status] ?? 'warn'">{{ $statusLabels[$exam->status] ?? $exam->status }}</x-admin.badge>
          </div>
          <div class="adm-meta">
            <span><x-admin.icon name="cal"/>{{ $exam->starts_at ? \App\Support\PersianDate::format($exam->starts_at, 'Y/m/d H:i') : 'بدون زمان‌بندی' }}</span>
            <span><x-admin.icon name="clock"/>{{ number_format($exam->duration_minutes) }} دقیقه</span>
            <span><x-admin.icon name="file"/>{{ number_format($exam->questions_count) }} سؤال</span>
            <span><x-admin.icon name="chart"/>{{ number_format($exam->results_count) }} نتیجه</span>
          </div>
          <div class="adm-work-item" data-tone="track">
            <span><x-admin.icon name="link"/></span>
            <div><b>{{ $exam->provider === 'quiz24' ? 'آزمون متصل به Quiz24' : 'آزمون داخلی شیخان' }}</b><small>{{ $exam->provider === 'quiz24' ? ($exam->integration_status ?: 'در انتظار بررسی اتصال') : 'مدیریت سؤال و تصحیح در سامانه' }}</small></div>
          </div>
          @if($exam->integration_note)<p class="adm-panel-kicker">{{ $exam->integration_note }}</p>@endif
          <div class="adm-actions">
            @if($exam->provider === 'internal' || ! in_array($exam->integration_status, ['active', 'connected'], true))
              <x-admin.btn size="sm" icon="file" :href="route('admin.exams.questions', $exam)">مدیریت سؤال‌ها</x-admin.btn>
            @endif
            <x-admin.btn size="sm" variant="soft" icon="chart" :href="route('admin.exams.results', $exam)">نتایج</x-admin.btn>
            <x-admin.btn size="sm" variant="soft" icon="check" :href="route('admin.exams.attempts', $exam)">تصحیح تشریحی</x-admin.btn>
            <x-admin.btn size="sm" variant="soft" icon="edit" data-modal-open="exam-modal" data-title="ویرایش آزمون" :data-fill="json_encode($fill, JSON_UNESCAPED_UNICODE)" :data-action="route('admin.exams.update', $exam)" data-method="PATCH">ویرایش</x-admin.btn>
            @if(auth()->user()->hasRole('admin') && $exam->attempts_count === 0 && $exam->results_count === 0)
              <form method="POST" action="{{ route('admin.exams.destroy', $exam) }}" onsubmit="return confirm('این آزمون حذف شود؟')">
                @csrf @method('DELETE')
                <button type="submit" class="adm-btn adm-btn-line adm-btn-sm">حذف</button>
              </form>
            @endif
          </div>
        </div>
      </article>
    @empty
      <x-admin.empty text="هنوز آزمونی برای این محدوده دسترسی ثبت نشده است."/>
    @endforelse
  </div>
  <x-admin.empty data-empty class="adm-hide" text="آزمونی با این مشخصات پیدا نشد."/>
  <x-slot:footer>{{ $exams->links() }}</x-slot:footer>
</x-admin.panel>
@endsection

@push('modals')
<x-admin.modal :demo="false" id="exam-modal" title="آزمون جدید" size="lg" :action="url('admin/exams')">
  <x-admin.legend title="اطلاعات آزمون" desc="آزمون می‌تواند به یک دوره، یک کلاس یا هر دو متصل شود."/>
  <x-admin.input label="عنوان آزمون" name="title" wide required/>
  <x-admin.textarea label="توضیحات" name="description" wide/>
  <x-admin.select label="دوره مرتبط" name="course_id" :options="$courseOptions" placeholder="انتخاب دوره"/>
  <x-admin.select label="کلاس / گروه" name="classroom_id" :options="$classOptions" placeholder="انتخاب کلاس"/>
  <x-admin.select label="موتور آزمون" name="provider" :options="$providerOptions" required/>
  @unless($teacherView)
    <x-admin.input label="شناسه آزمون در Quiz24" name="external_id" placeholder="پس از ایجاد یا دریافت از سرویس"/>
  @endunless

  <x-admin.legend title="زمان و محدودیت شرکت"/>
  <x-admin.input label="زمان شروع" name="starts_at" type="datetime-local"/>
  <x-admin.input label="مدت آزمون" name="duration_hours" type="number" min="1" max="4" addon="ساعت" required/>
  <x-admin.input label="تعداد دفعات مجاز" name="attempts_allowed" type="number" min="1" max="5" required/>
  <x-admin.select label="وضعیت" name="status" :options="$statusOptions" required/>
  <x-admin.switch label="نتیجه برای والدین نیز قابل مشاهده باشد" name="show_to_guardians" wide/>
  <p class="adm-muted" style="grid-column:1/-1">آزمون داخلی شیخان حتی بدون اتصال Quiz24 قابل اجراست. انتخاب Quiz24 فقط درخواست اتصال را در کارتابل یکپارچه‌سازی ثبت می‌کند و تا تأیید API، اتصال خارجی فعال محسوب نمی‌شود.</p>
</x-admin.modal>
@endpush
