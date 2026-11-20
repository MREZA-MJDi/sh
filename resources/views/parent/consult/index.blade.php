@extends('parent.layouts.app')
@section('title', 'درخواست‌های مشاوره')
@section('content')
@php $kids = $children; $kid = $selected_child; @endphp
<div class="parent-page-heading"><div><span class="parent-eyebrow">پشتیبانی آموزشی</span><h1>درخواست‌های مشاوره</h1><p>درخواست‌ها و پاسخ‌های مربوط به فرزند انتخاب‌شده</p></div></div>
@include('parent.partials.kid-picker')
<section class="c">
    <div class="chead"><h3>درخواست‌های ثبت‌شده</h3><a class="b s" href="{{ route('guardian.consultations.create', ['child' => $selected_child?->id]) }}">درخواست مشاوره جدید</a></div>
    <form method="GET" action="{{ route('guardian.consultations.index') }}" class="parent-inbox-filters">
        <input type="hidden" name="child" value="{{ $selected_child?->id }}">
        <label><span class="sr-only">جست‌وجو</span><input type="search" name="q" maxlength="100" value="{{ $filters['q'] ?? '' }}" placeholder="جست‌وجو در موضوع درخواست"></label>
        <label><span class="sr-only">وضعیت</span><select name="status"><option value="">همه وضعیت‌ها</option><option value="pending" @selected(($filters['status'] ?? '') === 'pending')>در انتظار بررسی</option><option value="scheduled" @selected(($filters['status'] ?? '') === 'scheduled')>هماهنگ‌شده</option><option value="done" @selected(($filters['status'] ?? '') === 'done')>انجام‌شده</option><option value="cancelled" @selected(($filters['status'] ?? '') === 'cancelled')>لغوشده</option></select></label>
        <label><span class="sr-only">مرتب‌سازی</span><select name="sort"><option value="newest" @selected(($filters['sort'] ?? 'newest') === 'newest')>جدیدترین</option><option value="oldest" @selected(($filters['sort'] ?? '') === 'oldest')>قدیمی‌ترین</option><option value="topic" @selected(($filters['sort'] ?? '') === 'topic')>موضوع</option></select></label>
        <button class="b s" type="submit">اعمال فیلتر</button><a class="b s" href="{{ route('guardian.consultations.index', ['child' => $selected_child?->id]) }}">پاک‌کردن</a>
    </form>
    @forelse ($consultations as $consultation)
        <a class="row" href="{{ route('guardian.consultations.show', $consultation) }}">
            <div>{{ $consultation->topic }}<small>{{ $consultation->student?->name }} · {{ $consultation->created_at?->format('Y/m/d H:i') }}</small></div>
            @php $labels = ['pending'=>'در انتظار بررسی','scheduled'=>'هماهنگ‌شده','done'=>'انجام‌شده','cancelled'=>'لغوشده']; @endphp
            <i class="bd {{ $consultation->status === 'done' ? 'gn' : (in_array($consultation->status, ['cancelled'], true) ? 'rd' : 'am') }}">{{ $labels[$consultation->status] ?? 'در حال بررسی' }}</i>
        </a>
    @empty
        <div class="parent-empty-state"><strong>درخواستی برای این فرزند پیدا نشد.</strong><span>می‌توانید یک درخواست مشاوره جدید ثبت کنید.</span></div>
    @endforelse
    <div class="parent-pagination">{{ $consultations->links() }}</div>
</section>
@endsection
