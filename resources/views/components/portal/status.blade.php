@props(['value','domain'=>'message'])
@php
 $states = match($domain) {
  'exam' => ['published'=>['منتشرشده','success'],'scheduled'=>['زمان‌بندی‌شده','info'],'closed'=>['بسته‌شده','neutral'],'in_progress'=>['در حال آزمون','info'],'graded'=>['تصحیح‌شده','success'],'pending_review'=>['در انتظار تصحیح','warning'],'submitted'=>['ارسال‌شده','info'],'expired'=>['مهلت تمام شده','danger']],
  'homework' => ['pending'=>['در انتظار ارسال','warning'],'late'=>['مهلت گذشته','danger'],'submitted'=>['در انتظار بررسی','info'],'reviewed'=>['بررسی‌شده','success']],
  'attendance' => ['present'=>['حاضر','success'],'absent'=>['غایب','danger'],'late'=>['تأخیر','warning'],'excused'=>['غیبت موجه','info']],
  default => ['open'=>['باز','info'],'read'=>['خوانده‌شده','neutral'],'answered'=>['پاسخ داده‌شده','success'],'closed'=>['بسته‌شده','neutral'],'deleted'=>['حذف‌شده','danger']],
 };
 [$label,$tone] = $states[$value] ?? ['نامشخص','neutral'];
@endphp
<span {{ $attributes->merge(['class'=>'portal-status','data-status'=>$tone]) }}><span aria-hidden="true"></span>{{ $label }}</span>
