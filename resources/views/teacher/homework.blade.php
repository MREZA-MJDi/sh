@extends('teacher.layouts.app')
@section('title', 'تکالیف')
@section('content')
<div class="teacher-page-head"><div><span class="teacher-eyebrow">محتوای آموزشی</span><h1>تکالیف کلاس‌ها</h1><p>تمرین متنی یا فایل PDF تعریف کنید و پاسخ‌های ارسال‌شده را بررسی کنید.</p></div></div>
<div class="g g2"><section class="c"><h2>تعریف تکلیف جدید</h2>
@if(auth()->user()->hasPermissionTo('homework.manage.assigned'))
<form class="teacher-form" method="POST" action="{{ route('teacher.homework.store') }}" enctype="multipart/form-data">@csrf
<label>کلاس</label><select name="classroom_id" required><option value="">انتخاب کلاس تخصیص‌یافته</option>@foreach($classrooms as $classroom)<option value="{{ $classroom->id }}" @selected((string)old('classroom_id') === (string)$classroom->id)>{{ $classroom->name }}{{ $classroom->course?->title ? ' · '.$classroom->course->title : '' }}</option>@endforeach</select>
<label>عنوان تکلیف</label><input name="title" maxlength="180" value="{{ old('title') }}" required>
<label>شرح و دستورالعمل</label><textarea name="instructions" rows="5" maxlength="40000" placeholder="شرح تمرین را بنویسید…">{{ old('instructions') }}</textarea>
<label>مهلت تحویل (اختیاری)</label><input type="datetime-local" name="due_at" value="{{ old('due_at') }}">
<label class="teacher-file-field">پیوست PDF (اختیاری)<input type="file" name="attachment" accept=".pdf,application/pdf"></label>
<label>وضعیت انتشار</label><select name="status" required><option value="draft">پیش‌نویس</option><option value="published" @selected(old('status','published')==='published')>انتشار برای دانش‌آموزان</option></select>
<button class="b" type="submit">ثبت تکلیف</button><small class="teacher-help">حداقل یکی از شرح متنی یا فایل پیوست باید تکمیل شود. فایل‌ها خصوصی ذخیره می‌شوند.</small>
</form>
@else
<p>دسترسی شما برای تعریف تکلیف فعال نیست؛ می‌توانید تکلیف‌های کلاس‌های تخصیص‌یافته را مشاهده کنید.</p>
@endif
</section>
<section class="c"><h2>تکلیف‌های ثبت‌شده</h2>
@forelse($homework as $item)<div class="teacher-list-card"><div><strong>{{ $item->title }}</strong><small>{{ $item->classroom?->name }} · مهلت: <time data-jalali-display="{{ $item->due_at?->format('Y-m-d H:i') }}">{{ $item->due_at?->format('Y/m/d H:i') ?? 'بدون مهلت' }}</time></small></div><div class="teacher-progress"><span style="width:{{ min(100,$item->submissions_count*100/max($item->classroom?->students_count ?? 1,1)) }}%"></span></div><div class="teacher-list-meta"><span>{{ $item->submissions_count }} پاسخ ثبت‌شده</span><span>{{ $item->attachment_path ? 'دارای فایل پیوست' : 'متن / شرح' }}</span></div></div>
@empty<p>هنوز تکلیفی برای کلاس‌های شما ثبت نشده است.</p>@endforelse{{ $homework->links() }}</section></div>
<section class="c teacher-review-panel"><div class="teacher-section-head"><div><h2>بررسی و نمره‌دهی پاسخ‌ها</h2><p>فقط پاسخ‌های کلاس‌های تخصیص‌یافته به شما نمایش داده می‌شوند.</p></div></div>
@forelse($submissions as $submission)
<div class="teacher-review-row"><div><strong>{{ $submission->student?->name }}</strong><small>{{ $submission->homework?->title }}</small></div>
@if(auth()->user()->hasPermissionTo('homework.grade'))
<form method="POST" action="{{ route('teacher.homework.review',$submission) }}" class="teacher-review-form">@csrf @method('PATCH')<input class="grade" type="number" min="0" max="20" step="0.25" name="score" value="{{ $submission->score }}" placeholder="نمره" aria-label="نمره"><input type="text" name="feedback" maxlength="30000" required placeholder="بازخورد مدرس" aria-label="بازخورد مدرس"><button class="b s" type="submit">ثبت نتیجه</button></form>
@else
<span class="bd am">فقط مشاهده</span>
@endif
</div>
@empty<p>پاسخی برای بررسی در این صفحه وجود ندارد.</p>@endforelse{{ $submissions->links() }}
</section>
@endsection