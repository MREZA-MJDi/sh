@extends('parent.layouts.app')
@section('title', 'پیام جدید')
@section('content')
<h2>ارسال پیام به مدرس</h2>
@include('parent.partials.kid-picker')
<section class="c">
 <form method="POST" action="{{ route('guardian.messages.store') }}">
  @csrf
  <label>فرزند</label><select name="student_id" required>
   @foreach ($children as $child)<option value="{{ $child->id }}" @selected($child->id === $selected_child?->id)>{{ $child->name }}</option>@endforeach
  </select>
  <label>گیرنده</label><select name="recipient_id" required>
   @forelse ($recipients as $recipient)<option value="{{ $recipient->id }}">{{ $recipient->name }}</option>@empty<option value="">مدرس یا مدیری برای کلاس‌های فعال یافت نشد</option>@endforelse
  </select>
  <label>موضوع</label><input name="subject" minlength="3" maxlength="190" required>
  <label>متن پیام</label><textarea name="body" rows="5" minlength="5" maxlength="30000" required></textarea>
  <button class="b" type="submit" @disabled($recipients->isEmpty())>ارسال پیام</button>
 </form>
</section>
@endsection