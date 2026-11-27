@extends('public.layouts.academy')
@section('title', ($settings->home_sections['contact_page_title'] ?? 'تماس با ما') . ($settings->title_separator ?? ' | ') . ($settings->site_name ?? 'آکادمی شیخان'))
@section('description', $settings->home_sections['contact_page_description'] ?? $settings->default_description)

@section('content')
<header class="ac-page-head"><div class="ac-wrap"><div style="font-size:13px;color:#9fb0d8"><a href="{{ route('home') }}">خانه</a> ‹ تماس</div><h1>{{ $settings->home_sections['contact_page_title'] ?? 'تماس با ما' }}</h1><p>{{ $settings->home_sections['contact_page_description'] ?? $settings->default_description }}</p></div></header>
<div class="ac-wrap ac-contact-grid">
  <form class="ac-card ac-form-card" method="POST" action="{{ route('public.contact.store') }}">
    @csrf
    <h2>ارسال پیام</h2>
    @if($errors->any())
      <div class="ac-alert" role="alert">پیام ثبت نشد. موارد مشخص‌شده در فرم را اصلاح کنید.</div>
    @endif

    <label for="name">نام و نام خانوادگی</label>
    <input class="ac-field" id="name" name="name" value="{{ old('name') }}" required maxlength="120"
      @error('name') aria-invalid="true" aria-describedby="contact-name-error" @enderror>
    @error('name')<div class="ac-error" id="contact-name-error">{{ $message }}</div>@enderror

    <label for="contact">ایمیل یا شماره تماس</label>
    <input class="ac-field" id="contact" name="contact" value="{{ old('contact') }}" required maxlength="190"
      @error('contact') aria-invalid="true" aria-describedby="contact-address-error" @enderror>
    @error('contact')<div class="ac-error" id="contact-address-error">{{ $message }}</div>@enderror

    <label for="topic">موضوع پیام</label>
    <select class="ac-field" id="topic" name="topic" required
      @error('topic') aria-invalid="true" aria-describedby="contact-topic-error" @enderror>
      @foreach(($settings->home_sections['contact_topics'] ?? []) as $topic)
        <option value="{{ $topic }}" @selected(old('topic') === $topic)>{{ $topic }}</option>
      @endforeach
    </select>
    @error('topic')<div class="ac-error" id="contact-topic-error">{{ $message }}</div>@enderror

    <label for="body">پیام شما</label>
    <textarea class="ac-field" id="body" name="body" required maxlength="5000"
      @error('body') aria-invalid="true" aria-describedby="contact-body-error" @enderror>{{ old('body') }}</textarea>
    @error('body')<div class="ac-error" id="contact-body-error">{{ $message }}</div>@enderror
    <button class="ac-btn" style="width:100%" type="submit">ثبت پیام</button>
  </form>
<div><div class="ac-card ac-block"><h2>{{ $settings->home_sections['contact_support_title'] ?? 'پشتیبانی' }}</h2><p class="ac-muted">{{ $settings->home_sections['contact_support_body'] ?? '' }}</p><p class="ac-muted" style="font-size:13px">برای جلوگیری از نمایش اطلاعات نادرست، شماره تلفن و نشانی ثابت در این صفحه درج نشده است.</p></div><div class="ac-card ac-block"><h2>{{ $settings->home_sections['contact_faq_title'] ?? 'سؤالات متداول' }}</h2>@foreach(($settings->home_sections['contact_faq_items'] ?? []) as $faq)<details><summary>{{ $faq['question'] ?? '' }}</summary><p class="ac-muted">{{ $faq['answer'] ?? '' }}</p></details>@endforeach</div></div></div>
@endsection
