@extends('teacher.layouts.app')
@section('title', 'پیام‌های اولیا')
@section('content')
<h2>پیام‌های مرتبط با کلاس‌های من</h2>
<div class="g">
 <section class="c"><div class="chead"><h3>گفت‌وگوها</h3><a class="b" href="{{ route('teacher.messages.create') }}">پیام جدید</a></div>
  @forelse ($conversations as $conversation)
   <div class="row"><div>{{ $conversation->subject }}<small>{{ $conversation->student?->name ?? $conversation->sender?->name }} · {{ $conversation->created_at?->format('Y/m/d H:i') }}</small><p>{{ \\Illuminate\\Support\\Str::limit(strip_tags($conversation->body), 180) }}</p></div><i class="bd {{ $conversation->status === 'open' ? 'am' : 'gn' }}">{{ $conversation->status }}</i></div>
  @empty
   <p>پیامی برای کلاس‌های تحت مدیریت شما ثبت نشده است.</p>
  @endforelse
  {{ $conversations->links() }}
 </section>
</div>
@endsection