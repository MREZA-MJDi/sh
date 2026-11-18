@extends('teacher.layouts.app')
@section('title', 'پیام جدید')
@section('content')
<h2>ارسال پیام</h2>
<section class="c">
 <form class="teacher-form" method="POST" action="{{ route('teacher.messages.store') }}">
  @csrf
  <label>مخاطب</label>
  <select name="target" required>
   <option value="admin" @selected(old('target') === 'admin')>مدیریت آموزشگاه</option>
   @foreach ($classrooms as $classroom)
    <optgroup label="{{ $classroom->name }}">
     @foreach ($classroom->students as $student)
      <option value="student:{{ $classroom->id }}:{{ $student->id }}" @selected(old('target') === 'student:'.$classroom->id.':'.$student->id)>دانش‌آموز: {{ $student->name }}</option>
      @foreach ($student->guardians as $guardian)
       <option value="parent:{{ $classroom->id }}:{{ $student->id }}:{{ $guardian->id }}" @selected(old('target') === 'parent:'.$classroom->id.':'.$student->id.':'.$guardian->id)>ولی {{ $student->name }}: {{ $guardian->name }}</option>
      @endforeach
     @endforeach
    </optgroup>
   @endforeach
  </select>
  <label>موضوع</label><input name="subject" minlength="3" maxlength="190" required value="{{ old('subject') }}">
  <label>متن پیام</label><textarea name="body" rows="5" minlength="5" maxlength="30000" required>{{ old('body') }}</textarea>
  <button class="b" type="submit">ارسال پیام</button>
 </form>
</section>
@endsection