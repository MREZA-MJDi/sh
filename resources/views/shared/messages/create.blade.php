@extends(request()->routeIs('guardian.*') ? 'layouts.parent' : 'layouts.student')

@section('title', 'پیام جدید')

@section('content')
    @php
        $messageRoutePrefix = request()->routeIs('guardian.*') ? 'guardian.messages' : 'student.messages';
    @endphp

    <x-card title="ارسال پیام به مدرس یا مدیر آکادمی">
        @if($recipients->isEmpty())
            <div class="empty">در حال حاضر مدرس یا مدیر فعالی برای این دانش‌آموز قابل انتخاب نیست.</div>
        @else
            <form method="POST" action="{{ route($messageRoutePrefix.'.store') }}">
                @csrf
                <div class="fm">
                    @if(request()->routeIs('guardian.*'))
                        <div>
                            <label for="student_id">درباره‌ی فرزند</label>
                            <select class="fld" id="student_id" name="student_id" required>
                                @foreach($children as $child)
                                    <option value="{{ $child->id }}" @selected((int) old('student_id', $selected_child?->id) === $child->id)>
                                        {{ $child->name }} · {{ $child->grade ?? 'پایه ثبت نشده' }}
                                    </option>
                                @endforeach
                            </select>
                            <x-field-error name="student_id"/>
                        </div>
                    @else
                        <input type="hidden" name="student_id" value="{{ auth()->id() }}">
                    @endif

                    <div>
                        <label for="recipient_id">گیرنده</label>
                        <select class="fld" id="recipient_id" name="recipient_id" required>
                            <option value="">انتخاب مدرس یا مدیر</option>
                            @foreach($recipients as $recipient)
                                <option value="{{ $recipient->id }}" @selected((int) old('recipient_id') === $recipient->id)>
                                    {{ $recipient->name }}
                                </option>
                            @endforeach
                        </select>
                        <x-field-error name="recipient_id"/>
                    </div>

                    <div class="w">
                        <label for="subject">موضوع پیام</label>
                        <input class="fld" id="subject" name="subject" maxlength="190" value="{{ old('subject') }}" required>
                        <x-field-error name="subject"/>
                    </div>

                    <div class="w">
                        <label for="body">متن پیام</label>
                        <textarea class="fld" id="body" name="body" data-count="20000" data-rich-text required>{{ old('body') }}</textarea>
                        <x-field-error name="body"/>
                    </div>
                </div>

                <div class="frm">
                    <button class="btn" type="submit">ارسال پیام</button>
                    <a class="btn g" href="{{ route($messageRoutePrefix.'.index') }}">بازگشت</a>
                </div>
            </form>
        @endif
    </x-card>
@endsection
