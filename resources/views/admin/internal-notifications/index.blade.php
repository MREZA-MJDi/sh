@extends('admin.layouts.app')
@section('title', 'اعلان‌های درون‌پنلی')

@section('content')
@php
  $audienceLabels = ['all' => 'همه کاربران', 'students' => 'دانش‌آموزان', 'parents' => 'اولیا', 'teachers' => 'مدرسان', 'admins' => 'مدیران'];
@endphp

<x-admin.page-head tone="comm" icon="bell" title="اعلان‌های درون‌پنلی" desc="ارسال پیام مستقیم به زنگوله کاربران؛ مستقل از اطلاعیه‌های سایت عمومی">
  <x-admin.btn variant="line" icon="megaphone" :href="route('admin.announcements.index')">اطلاعیه‌های عمومی</x-admin.btn>
</x-admin.page-head>

<section class="adm-notice-intro" data-tone="comm">
  <span class="adm-notice-intro-icon"><x-admin.icon name="bell"/></span>
  <div>
    <b>این پیام فقط داخل پنل کاربران نمایش داده می‌شود</b>
    <p>برای اطلاع‌رسانی فوری، یادآوری یا پیام اختصاصی گروهی استفاده کن. این فرم چیزی در صفحه اصلی سایت یا فید اطلاعیه‌های عمومی منتشر نمی‌کند و پیامک هم ارسال نمی‌شود.</p>
  </div>
</section>

@if(session('success'))
  <div class="adm-alert adm-alert-success" role="status" aria-live="polite">{{ session('success') }}</div>
@endif
@if($errors->any())
  <div class="adm-alert adm-alert-danger" role="alert" aria-live="assertive">
    <b>ارسال انجام نشد؛ موارد مشخص‌شده را اصلاح کن.</b>
    <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
  </div>
@endif

<div class="adm-notice-layout">
  <section class="adm-panel adm-notice-composer" data-tone="comm">
    <header class="adm-panel-head">
      <div><h2>نوشتن اعلان جدید</h2><p>مخاطب را انتخاب کن، پیام را بنویس و پیش‌نمایش را ببین.</p></div>
      <x-admin.badge tone="comm">درون‌پنلی</x-admin.badge>
    </header>
    <form method="POST" action="{{ route('admin.internal-notifications.store') }}" class="adm-panel-body adm-notice-form">
      @csrf
      <label class="adm-field">
        <span>گیرندگان پیام</span>
        <select class="adm-select" name="audience" required data-notice-audience>
          @foreach($audiences as $key => $label)
            <option value="{{ $key }}" @selected(old('audience', 'all') === $key)>{{ $label }} · {{ number_format($counts[$key] ?? 0) }} نفر</option>
          @endforeach
        </select>
        @error('audience')<small class="adm-err">{{ $message }}</small>@enderror
        <small>فقط حساب‌های فعال این گروه پیام را دریافت می‌کنند.</small>
      </label>
      <label class="adm-field">
        <span>عنوان اعلان</span>
        <input class="adm-input" name="title" maxlength="180" value="{{ old('title') }}" placeholder="مثلاً تغییر زمان کلاس فردا" required data-notice-title>
        @error('title')<small class="adm-err">{{ $message }}</small>@enderror
      </label>
      <label class="adm-field">
        <span>متن پیام</span>
        <textarea class="adm-textarea" name="message" rows="5" maxlength="1000" placeholder="پیام را کوتاه، روشن و همراه با اقدام موردنیاز بنویس…" required data-notice-message>{{ old('message') }}</textarea>
        @error('message')<small class="adm-err">{{ $message }}</small>@enderror
      </label>
      <div class="adm-notice-optional">
        <b>دکمه اقدام (اختیاری)</b>
        <p>اگر کاربر باید وارد صفحه مشخصی شود، مسیر داخلی را وارد کن؛ مثلاً مسیر مشاهده برنامه کلاس.</p>
        <label class="adm-field"><span>مسیر داخلی</span><input class="adm-input" name="url" value="{{ old('url') }}" placeholder="/student/homework" dir="ltr" data-notice-url></label>
        @error('url')<small class="adm-err">{{ $message }}</small>@enderror
        <label class="adm-field"><span>متن دکمه</span><input class="adm-input" name="action_label" value="{{ old('action_label') }}" maxlength="80" placeholder="مشاهده جزئیات" data-notice-action></label>
        @error('action_label')<small class="adm-err">{{ $message }}</small>@enderror
      </div>
      <div class="adm-notice-submit">
        <span><x-admin.icon name="lock"/> ارسال فقط برای گروه انتخاب‌شده</span>
        <x-admin.btn type="submit" icon="send">ارسال اعلان</x-admin.btn>
      </div>
    </form>
  </section>

  <aside class="adm-notice-preview-wrap">
    <div class="adm-notice-preview-label">پیش‌نمایش برای گیرنده</div>
    <article class="adm-notice-preview" data-tone="comm">
      <div class="adm-notice-preview-top"><span><x-admin.icon name="bell"/></span><small>اعلان جدید · داخل پنل</small></div>
      <b data-notice-preview-title>{{ old('title') ?: 'عنوان اعلان اینجا نمایش داده می‌شود' }}</b>
      <p data-notice-preview-message>{{ old('message') ?: 'متن پیام را بنویس تا پیش‌نمایش آن را اینجا ببینی.' }}</p>
      <span class="adm-notice-preview-action" data-notice-preview-action @if(!old('url')) hidden @endif>{{ old('action_label') ?: 'مشاهده جزئیات' }}</span>
      <small class="adm-notice-preview-foot">نمایش در زنگوله بالای پنل کاربر</small>
    </article>
    <div class="adm-notice-audience-summary">
      <b>تعداد گیرندگان</b>
      <strong data-notice-recipient-count>{{ number_format($counts[old('audience', 'all')] ?? 0) }}</strong>
      <small>بر اساس حساب‌های فعال</small>
    </div>
  </aside>
</div>

<section class="adm-panel adm-notice-history" data-tone="comm">
  <header class="adm-panel-head"><div><h2>ارسال‌های اخیر</h2><p>وضعیت دریافت و خوانده‌شدن اعلان‌های ارسالی از این بخش</p></div></header>
  @if($broadcasts->isNotEmpty())
    <div class="adm-table-wrap">
      <table class="adm-table">
        <thead><tr><th>عنوان پیام</th><th>مخاطب</th><th>گیرندگان</th><th>خوانده‌شده</th><th>زمان ارسال</th></tr></thead>
        <tbody>
          @foreach($broadcasts as $broadcast)
            <tr>
              <td><b>{{ $broadcast->title }}</b><small>{{ \Illuminate\Support\Str::limit($broadcast->message, 100) }}</small></td>
              <td>{{ $audienceLabels[$broadcast->audience] ?? 'کاربران' }}</td>
              <td>{{ number_format($broadcast->recipient_count) }}</td>
              <td><span class="adm-notice-read-rate">{{ number_format($broadcast->read_count) }} خوانده‌شده</span></td>
              <td>{{ \App\Support\PersianDate::format($broadcast->created_at, 'Y/m/d H:i') }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    <footer class="adm-panel-foot">{{ $broadcasts->links() }}</footer>
  @else
    <div class="adm-queue-empty"><x-admin.icon name="bell"/><b>هنوز اعلانی از این بخش ارسال نشده</b><span>بعد از اولین ارسال، گزارش تعداد گیرندگان و خوانده‌شدن اینجا ثبت می‌شود.</span></div>
  @endif
</section>
@endsection
