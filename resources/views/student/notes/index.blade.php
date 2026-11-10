@extends('layouts.portal')

@section('title', 'یادداشت‌های من')

@section('content')
    <x-crumbs :items="['خانه‌ی یادگیری' => route('student.dashboard'), 'یادداشت‌های من' => null]"/>

    <x-card title="یادداشت جدید">
        <form method="POST" action="{{ route('student.notes.store') }}">
            @csrf
            <label class="sr" for="note-text">متن یادداشت</label>
            <textarea class="fld" id="note-text" name="text" data-count="2000" placeholder="یادداشت جدید خود را بنویسید…">{{ old('text') }}</textarea>
            <x-field-error name="text"/>
            <div class="frm">
                <button class="btn" type="submit">ذخیره‌ی یادداشت</button>
                <a class="btn g" href="{{ route('student.dashboard') }}">بازگشت به داشبورد</a>
            </div>
        </form>
    </x-card>

    <x-card title="یادداشت‌های ذخیره‌شده">
        @forelse($notes as $note)
            <article class="note {{ $note->tone }}">
                <form method="POST" action="{{ route('student.notes.update', $note) }}">
                    @csrf
                    @method('PATCH')
                    <label class="sr" for="note-{{ $note->id }}">ویرایش یادداشت</label>
                    <textarea class="fld" id="note-{{ $note->id }}" name="text" data-count="2000">{{ $note->body }}</textarea>
                    <x-field-error name="text"/>
                    <div class="frm">
                        <button class="btn s" type="submit">ذخیره‌ی تغییرات</button>
                        <span class="mu">{{ \App\Support\PersianDate::format($note->updated_at, 'Y/m/d H:i') }}</span>
                    </div>
                </form>
                <form method="POST" action="{{ route('student.notes.destroy', $note) }}" onsubmit="return confirm('این یادداشت حذف شود؟')">
                    @csrf
                    @method('DELETE')
                    <button class="btn s bad" type="submit">حذف یادداشت</button>
                </form>
            </article>
        @empty
            <div class="empty">هنوز یادداشتی ثبت نکرده‌اید.</div>
        @endforelse

        @if($notes->hasPages())
            <div class="frm">{{ $notes->links() }}</div>
        @endif
    </x-card>
@endsection
