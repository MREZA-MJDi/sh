@extends('teacher.layouts.app')

@section('title', 'پروفایل و تصویر مدرس')

@section('content')
<x-portal.page-head icon="user" tone="education" title="پروفایل و تصویر مدرس"/>

 <div class="g g2">
    <section class="c">
        <h3>اطلاعات فردی و تصویر</h3>
        <form method="POST" action="{{ route('teacher.profile.update') }}" enctype="multipart/form-data" class="teacher-form" style="display:flex;flex-direction:column;gap:16px">
            @csrf
            @method('PUT')

            <div style="display:flex;align-items:center;gap:18px;padding:14px;background:var(--bg);border:1px solid var(--ln);border-radius:14px">
                <div class="av" style="width:72px;height:72px;font-size:26px;overflow:hidden;flex:none;box-shadow:0 4px 10px rgba(0,0,0,0.1)">
                    @if($teacher->avatarUrl())
                        <img src="{{ $teacher->avatarUrl() }}" alt="{{ $teacher->name }}" style="width:100%;height:100%;object-fit:cover;border-radius:50%" id="teacherAvatarPreview">
                    @else
                        <span id="teacherAvatarInitial">{{ mb_substr($teacher->name, 0, 1) }}</span>
                        <img src="" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:50%;display:none" id="teacherAvatarPreview">
                    @endif
                </div>
                <div>
                    <label style="display:block;margin-bottom:6px;font-weight:700">عکس پرسنلی / آواتار مدرس</label>
                    <input type="file" name="avatar" accept="image/png,image/jpeg,image/webp" style="padding:6px;font-size:13px" onchange="previewTeacherAvatar(this)">
                    <small style="display:block;color:var(--mu);margin-top:4px">فرمت‌های مجاز: JPG، PNG یا WebP (حداکثر ۸ مگابایت)</small>
                    @if($teacher->avatarUrl())
                        <label style="display:inline-flex;align-items:center;gap:6px;margin-top:8px;font-size:13px;color:#ef4444;cursor:pointer">
                            <input type="checkbox" name="remove_avatar" value="1"> حذف عکس فعلی
                        </label>
                    @endif
                </div>
            </div>

            <div>
                <label style="display:block;margin-bottom:6px;font-weight:700">نام و نام خانوادگی</label>
                <input type="text" name="name" value="{{ old('name', $teacher->name) }}" required style="width:100%;padding:10px;border:1px solid var(--ln);border-radius:10px;background:var(--bg);color:var(--tx);font:inherit">
            </div>

            <div>
                <label style="display:block;margin-bottom:6px;font-weight:700">حوزه تدریس / تخصص</label>
                <input type="text" name="subject" value="{{ old('subject', $teacher->subject) }}" placeholder="مثلاً: شیمی، فیزیک، زیست‌شناسی" style="width:100%;padding:10px;border:1px solid var(--ln);border-radius:10px;background:var(--bg);color:var(--tx);font:inherit">
            </div>

            <div>
                <label style="display:block;margin-bottom:6px;font-weight:700">شماره موبایل</label>
                <input type="tel" name="phone" inputmode="tel" dir="ltr" data-phone-input value="{{ old('phone', $teacher->phone) }}" style="width:100%;padding:10px;border:1px solid var(--ln);border-radius:10px;background:var(--bg);color:var(--tx);font:inherit">
            </div>

            <div>
                <label style="display:block;margin-bottom:6px;font-weight:700">ایمیل (شناسه کاربری)</label>
                <input type="email" value="{{ $teacher->email }}" readonly disabled style="width:100%;padding:10px;border:1px solid var(--ln);border-radius:10px;background:var(--bg);color:var(--mu);font:inherit;opacity:0.7">
            </div>

            <div>
                <label style="display:block;margin-bottom:6px;font-weight:700">رزومه و معرفی کوتاه</label>
                <textarea name="profile_note" rows="4" placeholder="این متن در صفحه عمومی مدرس و دوره‌های آموزشی نمایش داده می‌شود." style="width:100%;padding:10px;border:1px solid var(--ln);border-radius:10px;background:var(--bg);color:var(--tx);font:inherit">{{ old('profile_note', $teacher->profile_note) }}</textarea>
            </div>

            <div style="display:flex;gap:10px;margin-top:10px">
                <button type="submit" class="b s">ذخیره تغییرات پروفایل</button>
                <a href="{{ route('teacher.dashboard') }}" class="b s" style="background:var(--bg);color:var(--tx);border:1px solid var(--ln)">انصراف</a>
            </div>
        </form>
    </section>

    <section class="c">
        <h3>پیش‌نمایش نمایش عمومی</h3>
        <p style="color:var(--mu);margin-bottom:14px">تصویر و مشخصات شما در صفحه عمومی مدرسان و دوره‌های آموزشی شما به این صورت نمایش داده می‌شود:</p>
        <div style="text-align:center;padding:24px;background:var(--bg);border:1px solid var(--ln);border-radius:16px">
            <div class="av" style="width:84px;height:84px;font-size:30px;margin:0 auto 12px;overflow:hidden">
                @if($teacher->avatarUrl())
                    <img src="{{ $teacher->avatarUrl() }}" alt="{{ $teacher->name }}" style="width:100%;height:100%;object-fit:cover;border-radius:50%">
                @else
                    {{ mb_substr($teacher->name, 0, 1) }}
                @endif
            </div>
            <h3 style="margin:0 0 6px">{{ $teacher->name }}</h3>
            <span style="display:inline-block;padding:3px 12px;border-radius:99px;background:var(--ps, #e0e7ff);color:var(--pri, #4f46e5);font-size:13px;font-weight:700">
                {{ $teacher->subject ?: 'مدرس' }}
            </span>
            @if($teacher->profile_note)
                <p style="color:var(--mu);font-size:13px;line-height:1.7;margin-top:14px;text-align:justify">
                    {{ $teacher->profile_note }}
                </p>
            @endif
        </div>
        <div style="margin-top:16px">
            <a href="{{ route('public.teachers.show', $teacher) }}" target="_blank" class="b s" style="display:inline-block">مشاهده صفحه عمومی مدرس ↗</a>
        </div>
    </section>
</div>

<script>
function previewTeacherAvatar(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('teacherAvatarPreview');
            const initial = document.getElementById('teacherAvatarInitial');
            if (preview) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            }
            if (initial) {
                initial.style.display = 'none';
            }
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
@endsection
