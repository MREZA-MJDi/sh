{{-- نیازمند: $state (خروجی CourseAccess::state) --}}
@php($key = $state['license']?->license_key)
@if ($key)
    <div class="license-box" style="border:1px solid #cbd5e1;border-radius:12px;padding:12px;margin:12px 0">
        <strong>لایسنس شما</strong>
        <div style="display:flex;gap:8px;margin-top:8px;align-items:center;flex-wrap:wrap">
            <input id="license-key" type="password" readonly value="{{ $key }}" dir="ltr"
                   style="flex:1;min-width:220px;font-family:monospace" autocomplete="off">
            <button type="button" onclick="(function(i){i.type=i.type==='password'?'text':'password'})(document.getElementById('license-key'))">نمایش / مخفی</button>
            <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('license-key').value).then(()=>this.textContent='کپی شد')">کپی</button>
        </div>
        <ol style="margin:12px 0 0;padding-inline-start:20px;line-height:1.9">
            <li>اپ SpotPlayer را نصب و باز کنید.</li>
            <li>کلید بالا را در بخش لایسنس وارد کنید؛ دوره به‌صورت خودکار اضافه می‌شود.</li>
            <li>این لایسنس به دستگاه شما قفل می‌شود؛ آن را با دیگران به اشتراک نگذارید.</li>
        </ol>
        @if (config('learning.spotplayer_app_url'))
            <a href="{{ config('learning.spotplayer_app_url') }}" target="_blank" rel="noopener">دانلود اپ SpotPlayer</a>
        @endif
    </div>
@endif
