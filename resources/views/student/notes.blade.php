@extends('layouts.app')
@section('title', 'یادداشت‌ها')
@section('content')
<x-card title="یادداشت جدید">
  <form method="POST" action="{{ route('student.notes.store') }}">@csrf
    <label class="sr" for="text">متن یادداشت</label><textarea class="fld" id="text" name="text" data-count="500" placeholder="یادداشت جدید…">{{ old('text') }}</textarea>
    <div class="frm"><button class="btn" type="submit">افزودن</button></div>
  </form>
</x-card>
<div class="g3">
  @forelse($m['notes'] as $n)
    <div class="note {{ $n['tone'] }}"><p>{{ $n['text'] }}</p><span class="mu">{{ $n['date'] }}</span>
      <form method="POST" action="{{ route('student.notes.destroy', $n['id']) }}">@csrf @method('DELETE')<button type="submit">حذف</button></form></div>
  @empty<div class="empty">یادداشتی ندارید.</div>@endforelse
</div>
@endsection
