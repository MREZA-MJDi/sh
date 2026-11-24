@extends('parent.layouts.app')
@section('title', 'درخواست مشاوره جدید')
@section('content')
@php $kids = $children; $kid = $selected_child; @endphp
<x-portal.page-head icon="message" tone="communication" title="درخواست مشاوره جدید" :back="route('guardian.consultations.index', ['child' => $selected_child?->id])"/>
@include('parent.partials.kid-picker')
<section class="c">
    <form method="POST" action="{{ route('guardian.consultations.store') }}" class="parent-simple-form">
        @csrf
        <label>فرزند</label>
        <select name="student_id" required>
            @foreach ($children as $child)
                <option value="{{ $child->id }}" @selected((string) old('student_id', $selected_child?->id) === (string) $child->id)>{{ $child->name }}</option>
            @endforeach
        </select>
        <label>نوع درخواست</label>
        <select name="type" required>
            <option value="ticket" @selected(old('type', 'ticket') === 'ticket')>پیام و تیکت</option>
            <option value="phone" @selected(old('type') === 'phone')>تماس تلفنی</option>
            <option value="in_person" @selected(old('type') === 'in_person')>حضوری</option>
        </select>
        <label>موضوع</label>
        <input name="topic" required minlength="3" maxlength="190" value="{{ old('topic') }}">
        <label>شماره تماس</label>
        <input type="tel" inputmode="tel" dir="ltr" data-phone-input name="contact_phone" maxlength="30" value="{{ old('contact_phone', auth()->user()->phone) }}">
        <label>زمان پیشنهادی</label>
        <input type="datetime-local" name="desired_at" min="{{ now()->addMinute()->format('Y-m-d\\TH:i') }}" value="{{ old('desired_at') }}">
        <label>توضیحات</label>
        <textarea name="body" required minlength="5" maxlength="5000">{{ old('body') }}</textarea>
        <button class="b" type="submit">ثبت درخواست</button>
    </form>
</section>
@endsection
