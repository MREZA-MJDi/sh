<header class="adm-topbar">
  <x-admin.btn variant="line" size="icon" icon="menu" class="adm-menu-btn" data-menu aria-label="باز کردن منو"/>

  <nav class="adm-crumbs" aria-label="مسیر">
    <a href="{{ url('admin') }}">پنل مدیریت</a>
    @if ($activeGroup !== null)
      <x-admin.icon name="chev"/><span>{{ $nav['groups'][$activeGroup]['label'] }}</span>
      <x-admin.icon name="chev"/><b>{{ $nav['groups'][$activeGroup]['items'][$activeItem]['label'] }}</b>
    @endif
  </nav>

  <div class="adm-topbar-tools">
    <label class="adm-search-field">
      <x-admin.icon name="search"/>
      <input class="adm-input" type="search" placeholder="جستجوی کاربر، دوره، کلاس…" aria-label="جستجو">
    </label>

    <x-admin.btn variant="line" size="icon" icon="moon" data-theme-toggle aria-label="تغییر تم"/>

    <details class="adm-menu">
      <summary class="adm-btn adm-btn-line adm-btn-icon" aria-label="اعلان‌ها" style="position:relative"><x-admin.icon name="bell"/><i class="adm-dot"></i></summary>
      <div class="adm-menu-pop">
        <header>اعلان‌های تازه</header>
        <a href="{{ url('admin/payments') }}"><x-admin.icon name="card"/><span>۷ پرداخت منتظر تأیید است<small>همین حالا</small></span></a>
        <a href="{{ url('admin/parent-messages') }}"><x-admin.icon name="msg"/><span>۵ پیام جدید از اولیا<small>۲۰ دقیقه پیش</small></span></a>
        <a href="{{ url('admin/consultations') }}"><x-admin.icon name="chat"/><span>۲ درخواست مشاوره جدید<small>امروز</small></span></a>
      </div>
    </details>

    <details class="adm-menu">
      <summary class="adm-avatar" aria-label="حساب کاربری" style="cursor:pointer">م</summary>
      <div class="adm-menu-pop" style="width:220px">
        <header>مدیر سامانه</header>
        <form method="POST" action="{{ url('logout') }}">@csrf<button type="submit"><x-admin.icon name="logout"/>خروج از حساب</button></form>
      </div>
    </details>
  </div>
</header>
