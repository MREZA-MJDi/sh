@php($s = $session)
<div class="panel-field"><label>عنوان جلسه</label><input class="panel-input" name="title" value="{{ $s?->title }}" required maxlength="180" placeholder="مثلاً جلسه پنجم: معادله درجه دو"></div>
<div class="panel-field"><label>نوع جلسه</label>
    <select class="panel-select" name="session_type" required>
        <option value="online" @selected(($s?->session_type ?? 'online') === 'online')>آنلاین (اسکای‌روم)</option>
        <option value="onsite" @selected($s?->session_type === 'onsite')>حضوری</option>
    </select>
</div>
<div class="panel-field"><label>زمان شروع</label><input class="panel-input" type="datetime-local" name="starts_at" value="{{ $s?->starts_at?->format('Y-m-d\TH:i') }}" required></div>
<div class="panel-field"><label>زمان پایان (برای جلسه آنلاین الزامی)</label><input class="panel-input" type="datetime-local" name="ends_at" value="{{ $s?->ends_at?->format('Y-m-d\TH:i') }}"></div>
<div class="panel-field"><label>لینک ورود (فقط جلسه آنلاین)</label><input class="panel-input" type="url" name="meeting_url" value="{{ $s?->meeting_url }}" dir="ltr" placeholder="{{ $classroom->meeting_url ?: 'https://www.skyroom.online/ch/...' }}"><small class="panel-muted">اگر خالی بماند، لینک اسکای‌روم خود کلاس استفاده می‌شود.</small></div>
<div class="panel-field"><label>وضعیت</label>
    <select class="panel-select" name="status" required>
        @foreach (['scheduled' => 'برگزار می‌شود', 'completed' => 'برگزار شد', 'cancelled' => 'لغو شد'] as $key => $label)
            <option value="{{ $key }}" @selected(($s?->status ?? 'scheduled') === $key)>{{ $label }}</option>
        @endforeach
    </select>
</div>
<div class="panel-field"><label>یادداشت مدرس (اختیاری)</label><textarea class="panel-textarea" name="teacher_notes" rows="3" maxlength="2000">{{ $s?->teacher_notes }}</textarea></div>