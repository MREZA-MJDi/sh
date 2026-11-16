@extends('student.layouts.app')
@section('title', 'یادداشت‌ها')
@section('content')
<section class="page" id="notes"><div class="card"><div class="chead"><h2>یادداشت‌ها</h2></div>
 <form class="frm" method="POST" action="{{ route('student.notes.store') }}">@csrf
  <textarea class="fld" name="body" placeholder="یادداشت جدید..." required></textarea>
  <input type="hidden" name="tone" value="default"><button class="btn s" type="submit">افزودن</button>
 </form>
 <div class="g3">
  @forelse ($notes as $note)
   <div class="note"><p>{{ $note->body }}</p><small>{{ $note->created_at?->format('Y/m/d H:i') }}</small>
    <form method="POST" action="{{ route('student.notes.destroy', $note) }}">@csrf @method('DELETE')<button class="pill" type="submit">حذف</button></form>
   </div>
  @empty
   <p>یادداشتی ثبت نشده است.</p>
  @endforelse
 </div>{{ $notes->links() }}
</div></section>
@endsection