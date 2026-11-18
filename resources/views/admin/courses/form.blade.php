@extends('admin.layouts.app')
@section('title', $isEditing ? 'ویرایش صفحه دوره' : 'ساخت دوره جدید')

@section('content')
@php
  $seasons = ['spring' => 'بهار', 'summer' => 'تابستان', 'autumn' => 'پاییز', 'winter' => 'زمستان'];
  $statuses = ['draft' => 'پیش‌نویس', 'published' => 'انتشار در سایت', 'archived' => 'بایگانی'];
  $featureLabels = [
    'recorded_video' => 'ویدیوهای آموزشی',
    'live_class' => 'کلاس آنلاین',
    'resources' => 'جزوه و فایل‌های آموزشی',
    'assignments' => 'تکلیف و تمرین',
    'exams' => 'آزمون و کوییز',
    'projects' => 'پروژه و ارزیابی مرحله‌ای',
  ];
  $formAction = $isEditing ? route('admin.courses.update', $course) : route('admin.courses.store');
@endphp

<x-admin.page-head tone="edu" icon="book" :title="$isEditing ? 'ویرایش صفحه دوره' : 'ساخت دوره جدید'"
  desc="اطلاعات را به همان ترتیبی وارد کنید که در صفحه عمومی دوره نمایش داده می‌شود.">
  @if($isEditing)
    <x-admin.btn variant="line" icon="external" :href="route('public.courses.show', ['slug' => $course->slug, 'preview' => 1])" target="_blank">پیش‌نمایش صفحه عمومی</x-admin.btn>
    <x-admin.btn variant="line" :href="route('admin.courses.content', $course)">مدیریت سرفصل‌ها و درس‌ها</x-admin.btn>
  @endif
  <x-admin.btn variant="line" :href="route('admin.courses.index')">بازگشت به دوره‌ها</x-admin.btn>
</x-admin.page-head>

<div class="adm-split" style="align-items:start">
  <main class="adm-stack">
    <form method="POST" action="{{ $formAction }}" class="adm-stack">
      @csrf
      @if($isEditing) @method('PATCH') @endif
      <input type="hidden" name="delivery_type" value="onsite">
      <input type="hidden" name="spotplayer_course_id" value="{{ old('spotplayer_course_id', $course->spotplayer_course_id) }}">

      <x-admin.panel title="۱. سربرگ صفحه عمومی دوره" desc="این بخش در بالای صفحه دوره، زیر مسیر خانه ← دوره‌ها نمایش داده می‌شود." data-tone="edu">
        <div class="adm-form">
          <x-admin.input label="عنوان اصلی دوره" name="title" :value="old('title', $course->title)" placeholder="مثلاً شیمی دوازدهم؛ آموزش مفهومی و مسئله‌محور" wide required/>
          <x-admin.input label="موضوع یا درس" name="subject" :value="old('subject', $course->subject)" placeholder="مثلاً شیمی، زیست‌شناسی، ریاضی"/>
          <x-admin.select label="پایه تحصیلی" name="academic_grade_id" :options="$grades->pluck('name', 'id')->all()" :selected="old('academic_grade_id', $course->academic_grade_id ?: \App\Models\AcademicGrade::resolveIdFromLegacyName($course->grade))" placeholder="انتخاب پایه" required/>
          <x-admin.select label="فصل دوره" name="season" :options="$seasons" :selected="old('season', $course->season)" placeholder="انتخاب فصل" required/>
          <x-admin.select label="مدرس دوره" name="teacher_id" :options="$teachers->pluck('name', 'id')->all()" :selected="old('teacher_id', $course->teacher_id)" placeholder="انتخاب مدرس"/>
          <x-admin.textarea label="توضیح کوتاه زیر عنوان" name="description" :value="old('description', $course->description)" wide rows="4" placeholder="این متن در سربرگ و معرفی دوره نمایش داده می‌شود."/>
        </div>
      </x-admin.panel>

      <x-admin.panel title="۲. ثبت‌نام و قیمت" desc="این اطلاعات در کارت ثبت‌نام کنار صفحه عمومی نمایش داده می‌شوند." data-tone="shop">
        <div class="adm-form">
          <x-admin.select label="نوع دوره" name="type" :options="['free' => 'رایگان', 'paid' => 'پرداختی']" :selected="old('type', $course->type ?: 'free')" required/>
          <x-admin.input label="قیمت به تومان" name="price" :value="old('price', $course->price)" inputmode="numeric" type="number" min="0" max="1000000000" placeholder="برای دوره رایگان صفر"/>
          <x-admin.select label="وضعیت نمایش در سایت" name="status" :options="$statuses" :selected="old('status', $course->status ?: 'draft')" required/>
        </div>
      </x-admin.panel>

      <x-admin.panel title="۳. امکاناتی که دانش‌آموز در صفحه دوره می‌بیند" desc="هر گزینه را فقط وقتی روشن کنید که آن خدمت واقعاً برای این دوره ارائه می‌شود." data-tone="edu">
        <div class="adm-form">
          @foreach($featureLabels as $key => $label)
            <x-admin.switch :label="$label" :name="'learning_features['.$key.']'" :checked="(bool) old('learning_features.'.$key, $course->featureEnabled($key))" wide/>
          @endforeach
        </div>
      </x-admin.panel>

      <div class="adm-actions">
        <button type="submit" class="adm-btn adm-btn-primary">{{ $isEditing ? 'ذخیره تغییرات صفحه دوره' : 'ساخت دوره و ادامه تکمیل صفحه' }}</button>
        @if($isEditing)
          <a class="adm-btn adm-btn-line" href="{{ route('public.courses.show', ['slug' => $course->slug, 'preview' => 1]) }}" target="_blank" rel="noopener">دیدن نتیجه در صفحه عمومی</a>
        @endif
      </div>
    </form>

    @if($isEditing)
      <x-admin.panel title="۴. ویدیوی معرفی / تریلر دوره" desc="این ویدیو در بخش معرفی صفحه عمومی قرار می‌گیرد. فایل به‌صورت تکه‌ای بارگذاری و سپس پردازش می‌شود." data-tone="shop">
        @if($course->teaser_status === 'ready')
          <video controls preload="metadata" style="display:block;width:100%;max-height:360px;border-radius:14px;background:#0f172a" src="{{ route('admin.courses.teaser', $course) }}"></video>
        @elseif($course->teaser_status === 'processing')
          <x-admin.badge tone="warn">در حال پردازش تریلر</x-admin.badge>
          <p class="adm-muted">پس از پایان پردازش، پیش‌نمایش ویدیو در همین قسمت نمایش داده می‌شود.</p>
        @elseif($course->teaser_status === 'failed')
          <x-admin.badge tone="bad">پردازش ناموفق</x-admin.badge>
          <p class="adm-muted">{{ $course->teaser_error ?: 'پردازش تریلر کامل نشد؛ فایل را دوباره بارگذاری کنید.' }}</p>
        @endif
        <div data-course-upload data-target="teaser" data-course-id="{{ $course->id }}"
          data-start-url="{{ route('admin.uploads.start') }}" data-chunk-url="{{ route('admin.uploads.chunk') }}"
          data-complete-url="{{ route('admin.uploads.complete') }}" data-cancel-url="{{ route('admin.uploads.cancel') }}" data-resume-url="{{ route('admin.uploads.resume') }}" data-status-url="{{ route('admin.courses.teaser.status', $course) }}"
          data-max-mb="{{ config('learning.max_teaser_mb') }}" data-chunk-mb="{{ config('learning.chunk_mb') }}">
          <label class="adm-field">
            <span class="adm-label">انتخاب فایل تریلر</span>
            <input class="adm-input" type="file" accept="video/mp4,video/quicktime,video/webm,video/x-matroska,video/x-m4v" data-upload-file>
            <small class="adm-muted">حداکثر {{ config('learning.max_teaser_mb') }} مگابایت · MP4، MOV، MKV یا WebM</small>
          </label>
          <div class="adm-actions" style="margin-top:12px">
            <button type="button" class="adm-btn adm-btn-primary" data-upload-start>بارگذاری تریلر</button>
            @if($course->teaser_status !== 'none')
              <form method="POST" action="{{ route('admin.courses.teaser.destroy', $course) }}" onsubmit="return confirm('تریلر فعلی حذف شود؟')">
                @csrf @method('DELETE')
                <button type="submit" class="adm-btn adm-btn-line">حذف تریلر</button>
              </form>
            @endif
          </div>
          <div class="adm-progress" data-upload-progress hidden><div class="adm-progress-bar" data-upload-bar></div></div>
          <p class="adm-muted" data-upload-status role="status" aria-live="polite">هنوز فایلی انتخاب نشده است.</p>
        </div>
      </x-admin.panel>

      @if($course->featureEnabled('recorded_video'))
        <x-admin.panel title="۵. ویدیوهای آموزشی دوره" desc="فایل‌های بارگذاری‌شده در فضای خصوصی همین سامانه نگهداری و بدون نیاز به لینک SpotPlayer برای دانش‌آموزان دارای دسترسی پخش می‌شوند. شناسه خارجی فقط برای ویدیوی موجود در SpotPlayer است." data-tone="edu">
          <div data-course-upload data-target="spotplayer_video" data-course-id="{{ $course->id }}"
            data-start-url="{{ route('admin.uploads.start') }}" data-chunk-url="{{ route('admin.uploads.chunk') }}"
            data-complete-url="{{ route('admin.uploads.complete') }}" data-cancel-url="{{ route('admin.uploads.cancel') }}" data-resume-url="{{ route('admin.uploads.resume') }}" data-status-url="{{ route('admin.uploads.status') }}" data-max-mb="{{ config('learning.max_lesson_mb') }}" data-chunk-mb="{{ config('learning.chunk_mb') }}">
            <div class="adm-form">
              <x-admin.input label="عنوان جلسه / ویدیو" name="video_title" placeholder="مثلاً جلسه اول: ساختار اتم" data-upload-title/>
              <x-admin.select label="گروه آموزشی (اختیاری)" name="classroom_id" :options="$classrooms->pluck('name', 'id')->all()" placeholder="کلاس را انتخاب کنید" data-upload-classroom/>
              <label class="adm-field">
                <span class="adm-label">جلسه مشخص</span>
                <select class="adm-select" name="class_session_id" data-upload-session required>
                  <option value="">ابتدا جلسه را انتخاب کنید</option>
                  @foreach($classrooms as $classroom)
                    @foreach($classroom->sessions as $session)
                      <option value="{{ $session->id }}" data-classroom-id="{{ $classroom->id }}">{{ $session->title ?: 'جلسه' }} · {{ $classroom->name }} · {{ $session->starts_at?->format('Y/m/d H:i') }}</option>
                    @endforeach
                  @endforeach
                </select>
              </label>
              <x-admin.input label="شناسه خارجی (در صورت وجود)" name="external_id" placeholder="در صورت داشتن شناسه SpotPlayer" data-upload-external/>
              <label class="adm-field wide">
                <span class="adm-label">فایل ویدیوی جلسه</span>
                <input class="adm-input" type="file" accept="video/mp4,video/quicktime,video/webm,video/x-matroska,video/x-m4v" data-upload-file>
                <small class="adm-muted">حداکثر {{ config('learning.max_lesson_mb') }} مگابایت · MP4، MOV، MKV یا WebM</small>
              </label>
            </div>
            <div class="adm-actions" style="margin-top:12px">
              <button type="button" class="adm-btn adm-btn-primary" data-upload-start>بارگذاری ویدیو</button>
            </div>
            <div class="adm-progress" data-upload-progress hidden><div class="adm-progress-bar" data-upload-bar></div></div>
            <p class="adm-muted" data-upload-status role="status" aria-live="polite">فایل‌ها به‌صورت امن و تکه‌ای ارسال می‌شوند. پس از انتخاب، پیش‌نمایش ویدیو همین‌جا نمایش داده می‌شود. اگر فایل یا شناسه SpotPlayer وارد نکنید، ویدیو فقط در کتابخانه خصوصی ذخیره می‌شود و تا آماده‌شدن اتصال، رایگان/بدون لینک پخش باقی می‌ماند.</p>
          </div>

          <div class="adm-divider"></div>
          <h3>ویدیوهای ثبت‌شده</h3>
          <div class="adm-side-list">
            @forelse($videos as $video)
              <div>
                <span class="adm-stat-ico" data-tone="edu"><x-admin.icon name="video"/></span>
                <div class="adm-grow"><b>{{ $video->title }}</b><small class="adm-muted">{{ $video->classroom?->name ?? 'در سطح کل دوره' }} · {{ $video->file_original_name ?: $video->external_id ?: 'فایل ثبت‌شده' }}</small>@if($video->processing_error)<small class="adm-muted">{{ $video->processing_error }}</small>@endif</div>
                @php
                  $videoState = match ($video->status) {
                    'active' => ['ok', 'آماده پخش'],
                    'processing' => ['warn', 'در حال پردازش'],
                    'failed' => ['bad', 'پردازش ناموفق'],
                    'waiting_contract' => ['warn', 'در انتظار قرارداد'],
                    default => ['mute', $video->status],
                  };
                @endphp
                <x-admin.badge :tone="$videoState[0]">{{ $videoState[1] }}</x-admin.badge>
              </div>
            @empty
              <x-admin.empty text="هنوز ویدیویی برای این دوره بارگذاری نشده است."/>
            @endforelse
          </div>
          <p class="adm-muted">فایل بارگذاری‌شده مستقیماً از پلیر داخلی پخش می‌شود و نیازی به شناسه یا لینک SpotPlayer ندارد. اگر فقط شناسه خارجی ثبت شود، تا زمان تکمیل اتصال رسمی، وضعیت در انتظار قرارداد می‌ماند.</p>
          @if($videos->hasPages())<div class="adm-pagination">{{ $videos->links() }}</div>@endif
        </x-admin.panel>
      @endif

      <x-admin.panel title="۶. سرفصل‌ها و متن درس‌ها" desc="ساختار فصل‌ها و درس‌ها در صفحه عمومی دقیقاً با همین ترتیب نمایش داده می‌شود.">
        <p class="adm-muted">برای افزودن عنوان فصل، توضیح فصل، عنوان درس و متن آموزشی، وارد ویرایشگر سرفصل‌ها شوید.</p>
        <x-admin.btn icon="book" :href="route('admin.courses.content', $course)">مدیریت فصل‌ها و متن درس‌ها</x-admin.btn>
      </x-admin.panel>
    @else
      <x-admin.panel title="مرحله بعد: تریلر، ویدیو و سرفصل‌ها" desc="ابتدا اطلاعات اصلی دوره را ذخیره کنید. سپس در همین صفحه، تریلر و ویدیوها را بارگذاری و فصل‌ها و متن درس‌ها را تکمیل کنید.">
        <p class="adm-muted">با ذخیره اولیه، صفحه ویرایش اختصاصی همین دوره باز می‌شود و تمام بخش‌های محتوایی در دسترس قرار می‌گیرند.</p>
      </x-admin.panel>
    @endif
  </main>

  <aside class="adm-stack">
    <x-admin.panel title="جایگاه اطلاعات در صفحه عمومی" desc="راهنمای ساده برای اینکه بدانید هر ورودی کجا دیده می‌شود." data-tone="edu">
      <div class="adm-side-list">
        <div><span class="adm-stat-ico"><x-admin.icon name="type"/></span><div class="adm-grow"><b>عنوان و توضیح کوتاه</b><small class="adm-muted">بالای صفحه عمومی، زیر مسیر راهنما</small></div></div>
        <div><span class="adm-stat-ico"><x-admin.icon name="user"/></span><div class="adm-grow"><b>مدرس و موضوع</b><small class="adm-muted">کنار مشخصات دوره و بخش معرفی مدرس</small></div></div>
        <div><span class="adm-stat-ico"><x-admin.icon name="video"/></span><div class="adm-grow"><b>تریلر</b><small class="adm-muted">بخش معرفی و پیش‌نمایش ویدیویی دوره</small></div></div>
        <div><span class="adm-stat-ico"><x-admin.icon name="list"/></span><div class="adm-grow"><b>فصل‌ها و متن درس‌ها</b><small class="adm-muted">بخش سرفصل‌های آموزشی</small></div></div>
        <div><span class="adm-stat-ico"><x-admin.icon name="wallet"/></span><div class="adm-grow"><b>نوع و قیمت</b><small class="adm-muted">کارت ثبت‌نام کنار صفحه دوره</small></div></div>
      </div>
    </x-admin.panel>
    @if($isEditing)
      <x-admin.panel title="پیش‌نمایش زنده صفحه عمومی" desc="همان صفحه‌ای که بازدیدکننده می‌بیند؛ برای دوره پیش‌نویس با دسترسی مدیر قابل مشاهده است.">
        <x-admin.btn icon="external" :href="route('public.courses.show', ['slug' => $course->slug, 'preview' => 1])" target="_blank">باز کردن پیش‌نمایش عمومی</x-admin.btn>
      </x-admin.panel>
    @endif
  </aside>
</div>
@endsection
