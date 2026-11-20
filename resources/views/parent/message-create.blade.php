@extends('parent.layouts.app')
@section('title', 'پیام جدید')
@section('content')
<h2>ارسال پیام به مدرس</h2>
@include('parent.partials.kid-picker')

<section class="c parent-message-card">
    <div class="parent-form-heading">
        <h3>گفت‌وگوی تازه</h3>
        <p>پیام شما فقط برای گیرنده انتخاب‌شده و افراد مجاز در گفت‌وگوی مربوط نمایش داده می‌شود.</p>
    </div>
    <form method="POST" action="{{ route('guardian.messages.store') }}" class="parent-form">
        @csrf
        <div class="parent-field">
            <label for="student_id">فرزند</label>
            <select id="student_id" name="student_id" required>
                @foreach ($children as $child)
                    <option value="{{ $child->id }}" @selected((string) old('student_id', $selected_child?->id) === (string) $child->id)>{{ $child->name }}</option>
                @endforeach
            </select>
            @error('student_id')<small class="field-error">{{ $message }}</small>@enderror
        </div>
        <div class="parent-field">
            <label for="recipient_id">گیرنده پیام</label>
            <select id="recipient_id" name="recipient_id" required @disabled($recipients->isEmpty())>
                @forelse ($recipients as $recipient)
                    <option value="{{ $recipient->id }}" @selected((string) old('recipient_id') === (string) $recipient->id)>{{ $recipient->name }}</option>
                @empty
                    <option value="">مدرس یا مدیری برای کلاس‌های فعال یافت نشد</option>
                @endforelse
            </select>
            @error('recipient_id')<small class="field-error">{{ $message }}</small>@enderror
        </div>
        <div class="parent-field parent-field-wide">
            <label for="subject">موضوع پیام</label>
            <input id="subject" name="subject" type="text" value="{{ old('subject') }}" minlength="3" maxlength="190" autocomplete="off" required placeholder="موضوع پیام را بنویسید">
            @error('subject')<small class="field-error">{{ $message }}</small>@enderror
        </div>
        <div class="parent-field parent-field-wide">
            <label for="body">متن پیام</label>
            <textarea id="body" name="body" rows="7" minlength="5" maxlength="30000" required placeholder="متن پیام خود را اینجا بنویسید…">{{ old('body') }}</textarea>
            <small class="field-hint">حداکثر ۳۰ هزار نویسه</small>
            @error('body')<small class="field-error">{{ $message }}</small>@enderror
        </div>
        <div class="parent-form-actions parent-field-wide">
            <a class="b s" href="{{ route('guardian.messages.index', ['child' => $selected_child?->id]) }}">انصراف</a>
            <button class="b" type="submit" @disabled($recipients->isEmpty())>ارسال پیام</button>
        </div>
    </form>
</section>
@endsection
