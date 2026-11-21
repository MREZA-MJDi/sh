@extends('student.layouts.app')

@section('title', 'حساب کاربری')

@section('content')
<section class="page" id="profile">
  @if(session('success'))
    <div style="background:#dcfce7;color:#166534;padding:12px 18px;border-radius:12px;margin-bottom:16px;font-weight:600">
      {{ session('success') }}
    </div>
  @endif
  @if($errors->any())
    <div style="background:#fee2e2;color:#991b1b;padding:12px 18px;border-radius:12px;margin-bottom:16px;font-weight:600">
      <ul style="margin:0;padding-inline-start:18px">
        @foreach($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <div class="card">
    <div class="chead"><h2>اطلاعات پروفایل و تصویر کاربر</h2></div>
    <form method="POST" action="{{ route('student.profile.update') }}" enctype="multipart/form-data">
      @csrf
      @method('PUT')

      <div style="display:flex;align-items:center;gap:20px;padding:16px;margin-bottom:20px;background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.08);border-radius:16px">
        <div style="width:84px;height:84px;border-radius:50%;overflow:hidden;flex:none;background:#8aa2b8;display:grid;place-items:center;box-shadow:0 4px 10px rgba(0,0,0,0.15)">
          @if($student->avatarUrl())
            <img src="{{ $student->avatarUrl() }}" alt="{{ $student->name }}" style="width:100%;height:100%;object-fit:cover" id="avatarPreview">
          @else
            <svg viewBox="0 0 120 120" width="100%" height="100%" id="avatarPlaceholder"><use href="#avatar"/></svg>
            <img src="" alt="" style="width:100%;height:100%;object-fit:cover;display:none" id="avatarPreview">
          @endif
        </div>
        <div>
          <label style="display:block;margin-bottom:6px;font-weight:700">تصویر پروفایل</label>
          <input type="file" name="avatar" accept="image/png,image/jpeg,image/webp" class="fld" style="padding:6px;font-size:13px" onchange="previewAvatar(this)">
          <small style="display:block;color:var(--mu,#94a3b8);margin-top:4px">فرمت‌های مجاز: JPG، PNG یا WebP (حداکثر ۸ مگابایت)</small>
          @if($student->avatarUrl())
            <label style="display:inline-flex;align-items:center;gap:6px;margin-top:8px;font-size:13px;color:#ef4444;cursor:pointer">
              <input type="checkbox" name="remove_avatar" value="1"> حذف تصویر فعلی
            </label>
          @endif
        </div>
      </div>

      <div class="pf">
        <div><label>نام و نام خانوادگی</label><input class="fld" name="name" value="{{ old('name', $student->name ?? '') }}" required></div>
        <div><label>شماره‌ی موبایل</label><input class="fld" type="tel" name="phone" inputmode="tel" dir="ltr" data-phone-input value="{{ old('phone', $student->phone ?? '') }}"></div>
        <div><label>ایمیل</label><input class="fld" name="email" value="{{ $student->email ?? '' }}" readonly></div>
        <div><label>پایه</label><input class="fld" name="grade" value="{{ $student->grade ?? '' }}" readonly></div>
      </div>

      <div class="frm">
        <button class="btn s" type="submit">ذخیره‌ی تغییرات</button>
        <a class="pill" href="{{ route('student.dashboard') }}">انصراف</a>
      </div>
    </form>
  </div>
</section>

<script>
function previewAvatar(input) {
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = function(e) {
      const preview = document.getElementById('avatarPreview');
      const placeholder = document.getElementById('avatarPlaceholder');
      if (preview) {
        preview.src = e.target.result;
        preview.style.display = 'block';
      }
      if (placeholder) {
        placeholder.style.display = 'none';
      }
    };
    reader.readAsDataURL(input.files[0]);
  }
}
</script>
@endsection
