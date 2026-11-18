@extends('student.layouts.app')
@section('title', 'یادداشت‌ها')
@section('content')
<section class="page" id="notes">
  @if(session('status'))
    <div style="background:#dcfce7;color:#166534;padding:12px 18px;border-radius:12px;margin-bottom:16px;font-weight:600">
      {{ session('status') }}
    </div>
  @endif
  @if($errors->any())
    <div style="background:#fee2e2;color:#991b1b;padding:12px 18px;border-radius:12px;margin-bottom:16px;font-weight:600">
      <ul style="margin:0;padding-inline-start:18px">
        @foreach($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

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
            <small>{{ $note->created_at?->format('Y/m/d H:i') }}</small>
            <form method="POST" action="{{ route('student.notes.destroy', $note) }}">
              @csrf
              @method('DELETE')
              <button class="pill" type="submit" style="color:#ef4444;cursor:pointer">حذف</button>
            </form>
          </div>
        </div>
      @empty
        <p style="color:var(--muted)">یادداشتی ثبت نشده است.</p>
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
