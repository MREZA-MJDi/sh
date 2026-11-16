@extends('student.layouts.app')
@section('title', 'پیام جدید')
@section('content')
<section class="page"><div class="card"><h2>ارسال پیام به مدرس</h2>
 <form method="POST" action="{{ route('student.messages.store') }}">
  @csrf
  <input type="hidden" name="student_id" value="{{ $student->id }}">
  <label>گیرنده</label><select name="recipient_id" required>
   @foreach ($recipients as $recipient)<option value="{{ $recipient->id }}">{{ $recipient->name }}</option>@endforeach
  </select>
  <label>موضوع</label><input name="subject" minlength="3" maxlength="190" required>
  <label>متن پیام</label><textarea name="body" rows="5" minlength="5" maxlength="30000" required></textarea>
  <button class="btn s" type="submit">ارسال پیام</button>
 </form>
</div></section>
@endsection