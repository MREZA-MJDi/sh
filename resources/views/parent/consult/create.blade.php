@extends('layouts.portal')

@section('title', 'درخواست جلسه‌ی مشاوره')

@section('content')
    <x-crumbs :items="['درخواست‌های مشاوره' => route('parent.consult.index'), 'درخواست جدید' => null]"/>

    <x-card title="ثبت درخواست">
        <form method="POST" action="{{ route('parent.consult.store') }}">
            @csrf
            <div class="fm">
                <div>
                    <label for="student_id">درباره‌ی فرزند</label>
                    <select class="fld" id="student_id" name="student_id" required>
                        @foreach($children as $child)
                            <option value="{{ $child->id }}" @selected((int) old('student_id', $selectedChild->id) === $child->id)>
                                {{ $child->name }} · {{ $child->grade ?? 'پایه ثبت نشده' }}
                            </option>
                        @endforeach
                    </select>
                    <x-field-error name="student_id"/>
                </div>

                <div>
                    <label for="type">نوع جلسه</label>
                    <select class="fld" id="type" name="type" required>
                        <option value="ticket" @selected(old('type', 'ticket') === 'ticket')>تیکت و پیام‌رسانی</option>
                        <option value="phone" @selected(old('type') === 'phone')>تماس تلفنی</option>
                        <option value="in_person" @selected(old('type') === 'in_person')>حضوری</option>
                    </select>
                    <x-field-error name="type"/>
                </div>

                <div class="w">
                    <label for="subject">موضوع درخواست</label>
                    <input class="fld" id="subject" name="subject" value="{{ old('subject') }}" maxlength="180" required>
                    <x-field-error name="subject"/>
                </div>

                <div>
                    <label for="desired_at">زمان پیشنهادی (به وقت تهران)</label>
                    <input class="fld" id="desired_at" name="desired_at" type="datetime-local" value="{{ old('desired_at') }}">
                    <x-field-error name="desired_at"/>
                </div>

                <div>
                    <label for="contact_phone">شماره‌ی تماس</label>
                    <input class="fld" id="contact_phone" name="contact_phone" value="{{ old('contact_phone', auth()->user()->phone) }}" inputmode="tel">
                    <x-field-error name="contact_phone"/>
                </div>

                <div class="w">
                    <label for="body">توضیحات</label>
                    <textarea class="fld" id="body" name="body" data-count="5000" required>{{ old('body') }}</textarea>
                    <x-field-error name="body"/>
                </div>
            </div>

            <div class="frm">
                <button class="btn" type="submit">ثبت درخواست</button>
                <a class="btn g" href="{{ route('parent.consult.index') }}">انصراف</a>
            </div>
        </form>
    </x-card>
@endsection
