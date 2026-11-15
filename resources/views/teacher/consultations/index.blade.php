@extends('layouts.panel')

@section('page-title', 'درخواست مشاوره')
@section('page-description', 'ثبت درخواست برای مدیریت آکادمی و پیگیری پاسخ در همین صفحه')

@section('content')
    <section class="panel-grid panel-grid-2">
        <article class="panel-card" data-tone="talk">
            <div class="panel-card-title"><div><h3>درخواست جدید از مدیریت</h3><p>درخواست مستقیماً در کارتابل مشاوره ادمین ثبت می‌شود.</p></div></div>
            @if($errors->any())
                <div class="panel-alert panel-alert-danger" role="alert">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>
            @endif
            <form method="POST" action="{{ route('teacher.consultations.store') }}" class="panel-form-grid">
                @csrf
                <div class="panel-field full"><label for="consult-topic">موضوع درخواست</label><input id="consult-topic" class="panel-input" name="topic" value="{{ old('topic') }}" maxlength="190" required></div>
                <div class="panel-field"><label for="consult-type">روش ارتباط</label><select id="consult-type" class="panel-select" name="type" required><option value="ticket" @selected(old('type')==='ticket')>پیام در پنل</option><option value="phone" @selected(old('type','phone')==='phone')>تماس تلفنی</option><option value="in_person" @selected(old('type')==='in_person')>جلسه حضوری</option></select></div>
                <div class="panel-field"><label for="consult-phone">شماره تماس</label><input id="consult-phone" class="panel-input" name="contact_phone" value="{{ old('contact_phone', auth()->user()->phone) }}" maxlength="30" dir="ltr"></div>
                <div class="panel-field full"><label for="consult-desired">زمان پیشنهادی (اختیاری)</label><input id="consult-desired" class="panel-input" type="datetime-local" name="desired_at" value="{{ old('desired_at') }}"></div>
                <div class="panel-field full"><label for="consult-body">شرح درخواست</label><textarea id="consult-body" class="panel-textarea" name="body" rows="6" maxlength="5000" required>{{ old('body') }}</textarea></div>
                <div class="full"><button class="panel-btn" type="submit">ثبت درخواست برای مدیریت</button></div>
            </form>
        </article>

        <article class="panel-card" data-tone="talk">
            <div class="panel-card-title"><div><h3>پیگیری درخواست‌های من</h3><p>آخرین وضعیت‌هایی که مدیریت ثبت کرده است.</p></div><span class="panel-badge">{{ number_format($consultations->total()) }} درخواست</span></div>
            <div class="panel-list">
                @forelse($consultations as $consultation)
                    <div class="panel-list-row">
                        <div class="panel-avatar">◷</div>
                        <div class="panel-list-main">
                            <strong>{{ $consultation->topic ?: 'درخواست مشاوره' }}</strong>
                            <span>{{ $consultation->created_at->format('Y/m/d H:i') }} · {{ $consultation->scheduled_at?->format('Y/m/d H:i') ?? 'زمان‌بندی نشده' }}</span>
                            @if($consultation->notes)<p>{{ \Illuminate\Support\Str::limit(strip_tags($consultation->notes), 180) }}</p>@endif
                        </div>
                        <span class="panel-badge {{ in_array($consultation->status, ['scheduled','done'], true) ? 'panel-badge-success' : ($consultation->status==='pending' ? 'panel-badge-warning' : '') }}">{{ ['pending'=>'در انتظار بررسی','scheduled'=>'هماهنگ‌شده','done'=>'انجام‌شده','cancelled'=>'لغوشده'][$consultation->status] ?? $consultation->status }}</span>
                    </div>
                @empty
                    <div class="panel-empty">هنوز درخواستی ثبت نکرده‌اید.</div>
                @endforelse
            </div>
            <div class="panel-pagination">{{ $consultations->links() }}</div>
        </article>
    </section>
@endsection
