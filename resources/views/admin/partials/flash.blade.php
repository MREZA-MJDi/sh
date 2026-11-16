{{-- Server-side flash messages → shown as toasts by toast.js --}}
@if (session('status'))<i data-flash="{{ session('status') }}" hidden></i>@endif
@if (session('error'))<i data-flash="{{ session('error') }}" data-tone="info" hidden></i>@endif
@if ($errors->any())
  <div class="adm-alert" role="alert"><x-admin.icon name="alert"/><div>ذخیره انجام نشد؛ فیلدهای مشخص‌شده را بررسی کنید.</div></div>
@endif
