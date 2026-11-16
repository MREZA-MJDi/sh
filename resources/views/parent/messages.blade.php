@extends('parent.layouts.app')
@section('title', 'پیام به مدرس')
@section('content')
<h2>پیام‌های من</h2>
@include('parent.partials.kid-picker')
<section class="c">
 <div class="chead"><h3>گفت‌وگوها</h3><a class="b" href="{{ route('guardian.messages.create', ['child' => $selected_child?->id]) }}">پیام جدید</a></div>
 @forelse ($messages as $message)
  <a class="row" href="{{ route('guardian.messages.show', $message) }}"><div>{{ $message->subject }}<small>{{ $message->sender?->name }} · {{ $message->created_at?->format('Y/m/d H:i') }} · {{ $message->student?->name }}</small><span>{{ \\Illuminate\\Support\\Str::limit(strip_tags($message->body), 150) }}</span></div><i class="bd {{ $message->status === 'open' ? 'am' : 'gn' }}">{{ $message->status }}</i></a>
 @empty
  <p>گفت‌وگویی برای این فرزند ثبت نشده است.</p>
 @endforelse
 {{ $messages->links() }}
</section>
@endsection