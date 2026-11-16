@extends('student.layouts.app')
@section('title', 'پیام به مدرس‌ها')
@section('content')
<section class="page" id="messages">
 <div class="card"><div class="chead"><h2>گفت‌وگوهای من</h2><a class="btn s" href="{{ route('student.messages.create') }}">پیام جدید</a></div>
  @forelse ($messages as $message)
   <a class="trk" href="{{ route('student.messages.show', $message) }}"><div><b>{{ $message->subject }}</b><span>{{ $message->recipient?->name ?? $message->sender?->name }} · {{ $message->created_at?->format('Y/m/d H:i') }}</span><p>{{ \\Illuminate\\Support\\Str::limit(strip_tags($message->body), 150) }}</p></div><span class="tag">{{ $message->status }}</span></a>
  @empty
   <p>گفت‌وگویی ثبت نشده است.</p>
  @endforelse
  {{ $messages->links() }}
 </div>
</section>
@endsection