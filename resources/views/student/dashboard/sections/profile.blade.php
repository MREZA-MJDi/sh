<section class="page" id="s-profile"><div class="card"><div class="ch"><h2>اطلاعات حساب</h2></div><div class="fm">
    <div><label>نام و نام خانوادگی</label><input class="fld" value="{{ auth()->user()->name }}" readonly></div>
    <div><label>موبایل</label><input class="fld" value="{{ auth()->user()->phone ?? 'ثبت نشده' }}" readonly></div>
    <div><label>ایمیل</label><input class="fld" value="{{ auth()->user()->email ?? 'ثبت نشده' }}" readonly></div>
    <div><label>پایه</label><input class="fld" value="{{ auth()->user()->grade ?? 'ثبت نشده' }}" readonly></div>
</div><p class="info" style="margin-top:10px">برای اصلاح اطلاعات حساب، از طریق پشتیبانی آموزشگاه اقدام کنید.</p></div></section>
