@extends('admin.layouts.app')
@section('title', 'دوره‌ها')

@section('content')
@php
  $grades = [
    '7' => 'هفتم', '8' => 'هشتم', '9' => 'نهم', '10' => 'دهم', '11' => 'یازدهم', '12' => 'دوازدهم',
    'هفتم' => 'هفتم', 'هشتم' => 'هشتم', 'نهم' => 'نهم', 'دهم' => 'دهم', 'یازدهم' => 'یازدهم', 'دوازدهم' => 'دوازدهم',
  ];
  $types = ['free' => 'رایگان', 'paid' => 'پرداختی'];
  $deliveryTypes = ['spotplayer' => 'ویدیوی محافظت‌شده (SpotPlayer)', 'skyroom' => 'کلاس آنلاین (Skyroom)', 'onsite' => 'حضوری / ترکیبی'];
  $statuses = ['draft' => 'پیش‌نویس', 'published' => 'منتشرشده', 'archived' => 'بایگانی‌شده'];
  $featureLabels = [
    'recorded_video' => 'آموزش ویدیویی',
    'live_class' => 'کلاس آنلاین و جلسات زنده',
    'resources' => 'جزوه و منابع آموزشی',
    'assignments' => 'تکلیف و پروژه',
    'exams' => 'آزمون و کوییز',
    'projects' => 'پروژه و ارزیابی مرحله‌ای',
  ];
  $teacherOptions = $teachers->pluck('name', 'id')->all();
@endphp

<x-admin.page-head tone="edu" icon="book" title="دوره‌ها" desc="مدیریت محصول آموزشی، امکانات یادگیری، مدرس و انتشار">
  @if($canManage)
    <x-admin.btn icon="plus" data-modal-open="course-modal">دوره جدید</x-admin.btn>
  @endif
</x-admin.page-head>

<div class="adm-stats">
  <x-admin.stat tone="edu" icon="book" label="کل دوره‌ها" :value="number_format($stats['courses'])" :hint="$stats['published'].' دوره منتشرشده'"/>
  <x-admin.stat tone="edu" icon="users" label="دانش‌آموزان فعال" :value="number_format($stats['students'])" hint="ثبت‌نام‌های معتبر"/>
  <x-admin.stat tone="edu" icon="file" label="درس‌های منتشرشده" :value="number_format($stats['lessons'])" hint="در سرفصل‌های دوره‌ها"/>
  <x-admin.stat tone="edu" icon="edit" label="پیش‌نویس‌ها" :value="number_format($stats['drafts'])" hint="منتظر تکمیل و انتشار"/>
</div>

<div data-filter class="adm-stack">
  <x-admin.panel flush>
    <x-admin.toolbar placeholder="جستجوی دوره یا مدرس…">
      <x-admin.filter-select key="grade" placeholder="همه پایه‌ها" :options="$grades"/>
      <div class="adm-toolbar-end"><x-admin.seg :options="['on' => 'منتشرشده', 'off' => 'غیرمنتشرشده']"/></div>
    </x-admin.toolbar>
  </x-admin.panel>

  <div class="adm-grid adm-cols-3" data-filter-list>
    @forelse ($courses as $course)
      @php
        $features = [];
        foreach ($featureLabels as $key => $label) {
          $features[$key] = $course->featureEnabled($key);
        }
        $teacherName = $course->teacher?->name ?? 'مدرس تعیین نشده';
        $fill = [
          'title' => $course->title,
          'teacher_id' => (string) ($course->teacher_id ?? ''),
          'grade' => (string) ($course->grade ?? ''),
          'subject' => $course->subject,
          'description' => $course->description,
          'type' => $course->type,
          'price' => $course->price,
          'delivery_type' => $course->delivery_type,
          'spotplayer_course_id' => $course->spotplayer_course_id,
          'status' => $course->status,
        ];
        foreach ($features as $featureKey => $enabled) {
          $fill['learning_features['.$featureKey.']'] = (bool) $enabled;
        }
        $published = $course->status === 'published';
      @endphp
      <article class="adm-card" data-state="{{ $published ? 'on' : 'off' }}" data-grade="{{ $course->grade }}" data-name="{{ $course->title.' '.$teacherName }}">
        <div class="adm-card-cover">
          @if ($published)
            <x-admin.badge tone="ok">منتشرشده</x-admin.badge>
          @elseif ($course->status === 'archived')
            <x-admin.badge tone="mute">بایگانی‌شده</x-admin.badge>
          @else
            <x-admin.badge tone="warn">پیش‌نویس</x-admin.badge>
          @endif
          <x-admin.icon name="book"/>
        </div>
        <div class="adm-card-body">
          <h3>{{ $course->title }}</h3>
          <div class="adm-meta">
            <span><x-admin.icon name="user"/>{{ $teacherName }}</span>
            @if($course->grade)<span>{{ $grades[$course->grade] ?? $course->grade }}</span>@endif
            <span class="adm-num"><x-admin.icon name="users"/>{{ number_format($course->active_enrollments_count) }} دانش‌آموز</span>
            <span class="adm-num"><x-admin.icon name="cls"/>{{ number_format($course->active_classrooms_count) }} کلاس</span>
            <span class="adm-num"><x-admin.icon name="file"/>{{ number_format($course->sections_count) }} فصل</span>
          </div>
          <div class="adm-meta" style="margin-top:10px">
            @foreach($featureLabels as $key => $label)
              @if($features[$key])
                <span class="adm-chip">{{ $label }}</span>
              @endif
            @endforeach
          </div>
          <div class="adm-card-foot">
            @if ($course->type === 'free')
              <span class="adm-price is-free">رایگان</span>
            @else
              <span class="adm-price adm-num">{{ number_format((int) $course->price) }} تومان</span>
            @endif
            <span class="adm-muted">{{ $deliveryTypes[$course->delivery_type] ?? $course->delivery_type }}</span>
          </div>
          <div class="adm-actions">
            @if($canManage)
              <x-admin.btn size="sm" variant="soft" icon="eye" :href="route('public.courses.show', ['slug' => $course->slug, 'preview' => 1])" target="_blank">پیش‌نمایش</x-admin.btn>
            @endif
            @if($canManageContent)
              <x-admin.btn size="sm" icon="book" :href="url('admin/courses/'.$course->id.'/content')">محتوا</x-admin.btn>
            @endif
            @if($canManage)
              <x-admin.btn variant="soft" size="sm" icon="edit" data-modal-open="course-modal" data-title="ویرایش دوره" :data-fill="json_encode($fill, JSON_UNESCAPED_UNICODE)" :data-action="url('admin/courses/'.$course->id)" data-method="PATCH">ویرایش</x-admin.btn>
              <x-admin.btn variant="bad" size="sm" icon="trash" data-modal-open="course-del" :data-name="$course->title" :data-action="url('admin/courses/'.$course->id)" aria-label="حذف"/>
            @endif
          </div>
        </div>
      </article>
    @empty
      <x-admin.empty text="هنوز دوره‌ای ثبت نشده است."/>
    @endforelse
  </div>
  @if($courses->hasPages())
    <div class="adm-pagination">{{ $courses->links() }}</div>
  @endif
</div>
@endsection

@push('modals')
@if($canManage)
<x-admin.modal :demo="false" id="course-modal" title="دوره جدید" size="lg" :action="url('admin/courses')">
  <x-admin.legend title="اطلاعات پایه دوره"/>
  <x-admin.input label="عنوان دوره" name="title" placeholder="مثلاً ریاضی نهم" wide required/>
  <x-admin.select label="مدرس" name="teacher_id" :options="$teacherOptions" placeholder="انتخاب مدرس"/>
  <x-admin.select label="پایه تحصیلی" name="grade" :options="$grades" placeholder="همه پایه‌ها"/>
  <x-admin.input label="موضوع / درس" name="subject" placeholder="مثلاً ریاضی"/>
  <x-admin.textarea label="توضیحات" name="description" wide/>

  <x-admin.legend title="مدل فروش و اتصال سرویس" desc="محصول آموزشی مستقل از سرویس ارائه ویدیو یا کلاس آنلاین تعریف می‌شود."/>
  <x-admin.select label="نوع فروش" name="type" :options="$types" required/>
  <x-admin.input label="قیمت (تومان)" name="price" inputmode="numeric" placeholder="برای دوره رایگان صفر"/>
  <x-admin.select label="سرویس پیش‌فرض اتصال قدیمی" name="delivery_type" :options="$deliveryTypes" required/>
  <x-admin.input label="شناسه دوره در SpotPlayer" name="spotplayer_course_id" placeholder="پس از دریافت شناسه از پنل سرویس"/>
  <p class="adm-muted" style="grid-column:1/-1">امکانات آموزشی از ماتریس پایین تعیین می‌شوند. این فیلد فقط سازگاری با تنظیمات قدیمی دوره را حفظ می‌کند؛ دوره می‌تواند هم‌زمان ویدیو و کلاس زنده داشته باشد.</p>
  <p class="adm-muted" style="grid-column:1/-1">ثبت شناسه به معنی اتصال موفق نیست؛ وضعیت واقعی اتصال در بخش یکپارچه‌سازی نمایش داده می‌شود.</p>

  <x-admin.legend title="امکانات آموزشی این دوره" desc="امکانات مستقل‌اند؛ یک دوره می‌تواند چند مدل یادگیری را هم‌زمان داشته باشد."/>
  <x-admin.switch label="آموزش ویدیویی" name="learning_features[recorded_video]" wide/>
  <x-admin.switch label="کلاس آنلاین و جلسات زنده" name="learning_features[live_class]" wide/>
  <x-admin.switch label="جزوه و منابع آموزشی" name="learning_features[resources]" wide/>
  <x-admin.switch label="تکلیف و تمرین" name="learning_features[assignments]" wide/>
  <x-admin.switch label="آزمون و کوییز" name="learning_features[exams]" wide/>
  <x-admin.switch label="پروژه و ارزیابی مرحله‌ای" name="learning_features[projects]" wide/>

  <x-admin.legend title="وضعیت انتشار"/>
  <x-admin.select label="وضعیت دوره" name="status" :options="$statuses" required/>
</x-admin.modal>

<x-admin.confirm :demo="false" id="course-del" title="حذف دوره" text="حذف فقط برای دوره بدون سابقه آموزشی، ثبت‌نام و محتوای وابسته مجاز است؛ در غیر این صورت دوره باید بایگانی شود."/>
@endif
@endpush
