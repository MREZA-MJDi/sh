@extends('student.layouts.app')
@section('title', 'یادداشت‌ها')
@section('content')
<section class="page" id="notes">
<x-portal.page-head icon="file" tone="education" title="یادداشت‌ها"/>

  <div class="card">
    <div class="chead"><h2>یادداشت‌ها</h2></div>
    <form class="frm" method="POST" action="{{ route('student.notes.store') }}">
      @csrf
      <textarea class="fld" name="body" placeholder="یادداشت جدید..." required>{{ old('body') }}</textarea>
      <input type="hidden" name="tone" value="default">
      <button class="btn s" type="submit">افزودن</button>
    </form>
    <div class="g3" style="margin-top:20px">
      @forelse ($notes as $note)
        <div class="note">
          <p style="white-space:pre-wrap;word-break:break-word;margin-bottom:8px">{{ $note->body }}</p>
          <div style="display:flex;align-items:center;justify-content:space-between;margin-top:12px;border-top:1px dashed rgba(0,0,0,0.1);padding-top:8px">
            <small>{{ \App\Support\PersianDate::format($note->created_at) }}</small>
            <form method="POST" action="{{ route('student.notes.destroy', $note) }}">
              @csrf
              @method('DELETE')
              <button class="pill" type="submit" data-confirm="این یادداشت حذف شود؟">حذف</button>
            </form>
          </div>
        </div>
      @empty
        <x-portal.empty-state icon="file" title="یادداشتی ثبت نشده است."/>
      @endforelse
    </div>
    @if($notes->hasPages())
      <div style="margin-top:20px">
        {{ $notes->links() }}
      </div>
    @endif
  </div>
</section>
@endsection
