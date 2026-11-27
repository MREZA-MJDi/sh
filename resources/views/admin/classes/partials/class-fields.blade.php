@php
  $teacherOptions = $teacherOptions ?? [];
  $courseOptions = $courseOptions ?? [];
  $days = $days ?? ['sat' => 'شنبه', 'sun' => 'یکشنبه', 'mon' => 'دوشنبه', 'tue' => 'سه‌شنبه', 'wed' => 'چهارشنبه', 'thu' => 'پنجشنبه', 'fri' => 'جمعه'];
  $statusOptions = $statusOptions ?? ['active' => 'فعال', 'paused' => 'متوقف', 'archived' => 'بایگانی'];
@endphp
<x-admin.legend title="اطلاعات کلاس"/>
<x-admin.input label="نام کلاس / گروه" name="name" wide required/>
@if(isset($fixedCourseId))
  <input type="hidden" name="course_id" value="{{ $fixedCourseId }}">
@else
  <x-admin.select label="دوره مرتبط" name="course_id" :options="$courseOptions" :required="$teacherView ?? false" placeholder="کلاس مستقل / بدون دوره"/>
@endif
@if(!($teacherView ?? false))
  <x-admin.select label="مدرس مسئول" name="teacher_id" :options="$teacherOptions" required/>
@endif
<x-admin.select label="وضعیت کلاس" name="status" :options="$statusOptions" :selected="'active'" required/>
<details class="adm-history-panel wide" @if($errors->hasAny(['meeting_url','schedule_days','schedule_days.*','schedule_time','duration_minutes'])) open @endif>
  <summary>برنامه هفتگی و لینک کلاس <x-admin.icon name="chev"/></summary>
  <div class="adm-form">
<x-admin.input label="لینک پیش‌فرض جلسه (اختیاری)" name="meeting_url" placeholder="لینک ورود به کلاس آنلاین" wide/>
<x-admin.legend title="روزها و ساعت معمول کلاس"/>
<x-admin.chips label="روزهای برگزاری" name="schedule_days" :options="$days" hint="اگر روز ثابتی ندارد، خالی بگذار."/>
<x-admin.input label="ساعت شروع معمول" name="schedule_time" type="time"/>
<x-admin.input label="مدت هر جلسه" name="duration_minutes" type="number" min="15" max="300" addon="دقیقه"/>
<p class="adm-muted adm-grid-note">این برنامه، روزها و ساعت معمول کلاس را نشان می‌دهد؛ برای ساخت جلسه‌های واقعی، از تقویم کلاس استفاده کن.</p>
</div>
</details>
@if(isset($studentOptions))
  <input type="hidden" name="student_ids_present" value="1">
  <x-admin.legend title="اعضای کلاس"/>
  <x-admin.chips label="دانش‌آموزان فعال" name="student_ids" :options="$studentOptions" scroll hint="افزودن یا حذف عضو، تاریخچه عضویت را حفظ می‌کند"/>
  <p class="adm-muted adm-grid-note">کلاس و اعضای آن را همین‌جا ثبت کن؛ زمان‌بندی جلسه‌ها هم از تقویم کلاس در دسترس است.</p>
@endif
