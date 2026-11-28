@php
  $sessionTypeOptions = ['online' => 'آنلاین', 'onsite' => 'حضوری'];
  $sessionStatusOptions = ['scheduled' => 'برنامه‌ریزی‌شده', 'completed' => 'برگزارشده', 'cancelled' => 'لغوشده'];
  $sessionProviderOptions = ['skyroom' => 'اتاق Skyroom کلاس', 'jitsi' => 'لینک Jitsi (ورود با لینک دستی)', 'bbb' => 'لینک BigBlueButton (ورود با لینک دستی)', 'link' => 'لینک دلخواه'];
  $sessionDefaultDate = $sessionDefaultDate ?? now()->setTime(9, 0)->format('Y-m-d\\TH:i');
@endphp
<x-admin.input label="موضوع جلسه" name="title" wide required placeholder="مثلاً جلسه سوم: حل تمرین"/>
<x-admin.legend title="زمان و شیوه برگزاری"/>
<x-admin.select label="نوع برگزاری" name="session_type" :options="$sessionTypeOptions" :selected="'onsite'" required/>
<x-admin.select label="وضعیت" name="status" :options="$sessionStatusOptions" :selected="'scheduled'" required/>
<x-admin.input label="زمان شروع" name="starts_at" type="datetime-local" :value="$sessionDefaultDate" required/>
<x-admin.input label="زمان پایان" name="ends_at" type="datetime-local"/>
<p class="adm-muted adm-grid-note">برای جلسهٔ آنلاین، زمان پایان را هم ثبت کن. اگر از اتاق Skyroom کلاس استفاده می‌کنی، می‌توانی فیلد لینک جلسه را خالی بگذاری.</p>
<details class="adm-history-panel wide" @if($errors->hasAny(['meeting_provider','meeting_url','teacher_notes'])) open @endif>
  <summary>لینک جلسه آنلاین و یادداشت مدرس <x-admin.icon name="chev"/></summary>
  <div class="adm-form">
<x-admin.select label="سرویس یا نوع لینک" name="meeting_provider" :options="$sessionProviderOptions" :selected="'skyroom'"/>
<x-admin.input label="لینک ورود به جلسه (اختیاری)" name="meeting_url" wide placeholder="https://..."/>
<p class="adm-muted adm-grid-note">اتاق Skyroom از تنظیمات کلاس خوانده می‌شود. برای سرویس‌های دیگر، لینک آماده را وارد کن؛ انتخاب نام سرویس به‌تنهایی اتاق جدیدی ایجاد نمی‌کند.</p>
<x-admin.textarea label="یادداشت مدرس" name="teacher_notes" wide/>
</div>
</details>
