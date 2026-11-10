@extends(request()->routeIs('guardian.*') ? 'layouts.parent' : 'layouts.student')

@section('title', 'پیام‌ها')

@section('content')
    @php
        $messageRoutePrefix = request()->routeIs('guardian.*') ? 'guardian.messages' : 'student.messages';
        $createParameters = request()->routeIs('guardian.*') && $selected_child
            ? ['child' => $selected_child->id]
            : [];
    @endphp

    <x-card title="گفتگوهای من">
        <x-slot:actions>
            <a class="btn s" href="{{ route($messageRoutePrefix.'.create', $createParameters) }}">پیام جدید</a>
        </x-slot:actions>

        @forelse($messages as $message)
            <a class="trk" href="{{ route($messageRoutePrefix.'.show', $message) }}">
                <div class="th t-{{ ['a','b','c','d'][$loop->index % 4] }}"><x-icon name="msg"/></div>
                <div>
                    <b>{{ $message->subject }}</b>
                    <span class="mu">
                        @if(request()->routeIs('guardian.*'))
                            {{ $message->student?->name ?? 'دانش‌آموز' }} ·
                        @endif
                        {{ $message->sender_id === auth()->id() ? 'ارسال‌شده توسط شما' : 'ارسال‌شده برای شما' }}
                        · {{ \App\Support\PersianDate::format($message->created_at, 'Y/m/d H:i') }}
                    </span>
                </div>
                @if($message->status === 'answered')
                    <x-tag>پاسخ داده‌شده</x-tag>
                @elseif($message->status === 'closed')
                    <x-tag tone="d">بسته‌شده</x-tag>
                @else
                    <x-tag tone="w">در انتظار پاسخ</x-tag>
                @endif
            </a>
        @empty
            <div class="empty">هنوز گفتگویی ثبت نشده است.</div>
        @endforelse

        @if($messages->hasPages())
            <div class="frm">{{ $messages->links() }}</div>
        @endif
    </x-card>
@endsection
