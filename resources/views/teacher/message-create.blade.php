@extends('teacher.layouts.app')
@section('title', 'ارسال پیام')
@section('content')
<x-portal.page-head icon="message" tone="communication" title="ارسال پیام" :back="route('teacher.messages.index')" back-label="بازگشت به صندوق پیام"/>
<section class="c">
 <form class="teacher-form" method="POST" action="{{ route('teacher.messages.store') }}">
  @csrf
  <label for="recipient-search">جستجوی دانش‌آموز یا ولی</label>
  <div class="recipient-search">
   <input id="recipient-search" type="search" minlength="2" maxlength="80" placeholder="نام یا شماره تماس را وارد کنید" autocomplete="off">
   <button class="b s" type="button" data-recipient-search data-endpoint="{{ route('teacher.messages.recipients') }}">جستجو</button>
  </div>
  <p class="teacher-help" data-recipient-status aria-live="polite">برای حفظ سرعت، فقط نتایج مرتبط با کلاس‌های فعال شما جستجو می‌شوند.</p>
  <label for="recipient-target">مخاطب پیام</label>
  <select id="recipient-target" name="target" required>
   <option value="">ابتدا مخاطب را جستجو و انتخاب کنید</option>
   <option value="admin" @selected(old('target') === 'admin')>مدیریت آموزشگاه</option>
  </select>
  <label for="message-subject">موضوع</label>
  <input id="message-subject" name="subject" minlength="3" maxlength="190" required value="{{ old('subject') }}">
  <label for="message-body">متن پیام</label>
  <textarea id="message-body" name="body" rows="5" minlength="5" maxlength="30000" required>{{ old('body') }}</textarea>
  <button class="b" type="submit">ارسال پیام</button>
 </form>
</section>
@endsection

@push('scripts')
<script>
(() => {
 const button = document.querySelector('[data-recipient-search]');
 const input = document.querySelector('#recipient-search');
 const select = document.querySelector('#recipient-target');
 const status = document.querySelector('[data-recipient-status]');
 if (!button || !input || !select || !status) return;

 const search = async () => {
  const query = input.value.trim();
  if (query.length < 2) {
   status.textContent = 'برای جستجو حداقل دو حرف وارد کنید.';
   input.focus();
   return;
  }

  button.disabled = true;
  status.textContent = 'در حال جستجو…';
  try {
   const response = await fetch(button.dataset.endpoint + '?q=' + encodeURIComponent(query), {
    headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
    credentials: 'same-origin'
   });
   if (!response.ok) throw new Error('جستجوی مخاطبان انجام نشد.');
   const payload = await response.json();
   select.replaceChildren(new Option('ابتدا مخاطب را انتخاب کنید', ''));
   select.add(new Option('مدیریت آموزشگاه', 'admin'));
   (payload.data || []).forEach((item) => {
    select.add(new Option(item.label + ' · ' + item.meta, item.target));
   });
   status.textContent = payload.data?.length
    ? payload.data.length + ' نتیجه پیدا شد. مخاطب موردنظر را انتخاب کنید.'
    : 'مخاطبی در کلاس‌های فعال شما پیدا نشد.';
  } catch (error) {
   status.textContent = error.message || 'خطا در جستجوی مخاطب.';
  } finally {
   button.disabled = false;
  }
 };

 button.addEventListener('click', search);
 input.addEventListener('keydown', (event) => {
  if (event.key === 'Enter') {
   event.preventDefault();
   search();
  }
 });
})();
</script>
@endpush
