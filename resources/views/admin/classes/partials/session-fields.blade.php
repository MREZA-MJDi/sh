@php
  $sessionTypeOptions = ['online' => 'آنلاین', 'onsite' => 'حضوری'];
  $sessionStatusOptions = ['scheduled' => 'برنامه‌ریزی‌شده', 'completed' => 'برگزارشده', 'cancelled' => 'لغوشده'];
  $sessionProviderOptions = ['skyroom' => 'اتاق کلاس', 'jitsi' => 'سرویس ویدیویی دیگر', 'bbb' => 'سرویس ویدیویی دیگر (گزینه دوم)', 'link' => 'لینک دلخواه'];
  $sessionDefaultDate = $sessionDefaultDate ?? now()->setTime(9, 0)->format('Y-m-d\\TH:i');
@endphp
<x-admin.input label="موضوع جلسه" name="title" wide required placeholder="مثلاً جلسه سوم: حل تمرین"/>
<x-admin.select label="نوع برگزاری" name="session_type" :options="$sessionTypeOptions" :selected="'onsite'" required/>
<x-admin.select label="وضعیت" name="status" :options="$sessionStatusOptions" :selected="'scheduled'" required/>
<x-admin.input label="زمان شروع" name="starts_at" type="datetime-local" :value="$sessionDefaultDate" required/>
<x-admin.input label="زمان پایان" name="ends_at" type="datetime-local"/>
<p class="adm-muted adm-grid-note">برای جلسه آنلاین، زمان پایان را هم وارد کن. برای جلسه حضوری نیازی به لینک ورود نیست.</p>
<x-admin.select label="اتاق آنلاین" name="meeting_provider" :options="$sessionProviderOptions" :selected="'skyroom'"/>
<x-admin.input label="لینک جلسه (اختیاری)" name="meeting_url" wide placeholder="https://..."/>
<x-admin.textarea label="یادداشت مدرس" name="teacher_notes" wide/>
