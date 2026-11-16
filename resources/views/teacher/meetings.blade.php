@extends('teacher.layouts.app')
@section('title', 'جلسات با اولیا')
@section('content')
<h2>جلسات با اولیا</h2>
<section class="c">
 @forelse ($meetings as $meeting)
  <div class="row"><div>{{ $meeting->guardian?->name }} · {{ $meeting->student?->name }}<small>{{ $meeting->classroom?->name }} · {{ $meeting->title }} · {{ ($meeting->starts_at ?? $meeting->preferred_at)?->format('Y/m/d H:i') }}</small></div>
   <i class="bd {{ $meeting->status === 'scheduled' ? 'gn' : 'am' }}">{{ $meeting->status }}</i>
  </div>
 @empty
  <p>درخواست یا جلسه‌ای برای کلاس‌های شما ثبت نشده است.</p>
 @endforelse
 {{ $meetings->links() }}
</section>
@endsection