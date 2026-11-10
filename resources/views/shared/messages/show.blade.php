@extends(request()->routeIs('guardian.*') ? 'layouts.parent' : 'layouts.student')

@section('title', $message->subject)

@section('content')
    @php
        $messageRoutePrefix = request()->routeIs('guardian.*') ? 'guardian.messages' : 'student.messages';
        $canEditMessage = $message->sender_id === auth()->id()
            && $message->status === 'open'
            && $message->replies->isEmpty();
    @endphp

    <x-card title="{{ $message->subject }}">
        <x-slot:actions>
            <a class="btn s g" href="{{ route($messageRoutePrefix.'.index') }}">بازگشت به پیام‌ها</a>
        </x-slot:actions>

        <div class="msgs">
            <div class="m {{ $message->sender_id === auth()->id() ? 'me' : '' }}">
                <small>
                    {{ $message->sender?->name ?? 'کاربر آکادمی' }}
                    · {{ \App\Support\PersianDate::format($message->created_at, 'Y/m/d H:i') }}
                </small>
                <x-rich-content :value="$message->body"/>
            </div>

            @foreach($message->replies as $reply)
                <div class="m {{ $reply->sender_id === auth()->id() ? 'me' : '' }}">
                    <small>
                        {{ $reply->sender?->name ?? 'کاربر آکادمی' }}
                        · {{ \App\Support\PersianDate::format($reply->created_at, 'Y/m/d H:i') }}
                    </small>
                    <x-rich-content :value="$reply->body"/>
                </div>
            @endforeach
        </div>

        @if($canEditMessage)
            <div class="sep">ویرایش پیام ارسال‌شده</div>
            <form method="POST" action="{{ route($messageRoutePrefix.'.update', $message) }}">
                @csrf
                @method('PATCH')
                <div class="fm">
                    <div class="w">
                        <label for="subject">موضوع پیام</label>
                        <input class="fld" id="subject" name="subject" maxlength="190" value="{{ old('subject', $message->subject) }}" required>
                        <x-field-error name="subject"/>
                    </div>
                    <div class="w">
                        <label for="edit-body">متن پیام</label>
                        <textarea class="fld" id="edit-body" name="body" data-count="10000" required>{{ old('body', $message->body) }}</textarea>
                        <x-field-error name="body"/>
                    </div>
                </div>
                <div class="frm">
                    <button class="btn s" type="submit">ذخیره‌ی تغییرات</button>
                </div>
            </form>

            <form method="POST" action="{{ route($messageRoutePrefix.'.destroy', $message) }}" class="frm" onsubmit="return confirm('این پیام حذف شود؟')">
                @csrf
                @method('DELETE')
                <button class="btn s bad" type="submit">حذف پیام</button>
            </form>
        @endif

        @if($message->status !== 'closed')
            <div class="sep">ارسال پاسخ</div>
            <form method="POST" action="{{ route($messageRoutePrefix.'.reply', $message) }}">
                @csrf
                <label class="sr" for="reply-body">متن پاسخ</label>
                <textarea class="fld" id="reply-body" name="body" data-count="10000" placeholder="پاسخ خود را بنویسید…" required>{{ old('body') }}</textarea>
                <x-field-error name="body"/>
                <div class="frm">
                    <button class="btn" type="submit">ارسال پاسخ</button>
                </div>
            </form>
        @else
            <div class="info w" style="margin-top:14px">این گفتگو بسته شده است و امکان ارسال پاسخ جدید ندارد.</div>
        @endif
    </x-card>
@endsection
