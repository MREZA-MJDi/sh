@if (($kids ?? collect())->isNotEmpty())
    <nav class="parent-child-picker" aria-label="انتخاب فرزند">
        @if ($kid)<input type="hidden" value="{{ $kid->id }}" data-selected-child>@endif
        <div class="parent-child-picker-label">
            <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            <span>نمایش اطلاعات:</span>
        </div>
        <div class="parent-child-picker-links">
            @foreach (($kids ?? []) as $k)
                @php($isSelected = (int) data_get($k, 'id') === (int) data_get($kid ?? null, 'id'))
                <a href="{{ request()->fullUrlWithQuery(['child' => data_get($k, 'id'), 'page' => 1, 'homework' => 1, 'results' => 1, 'resources' => 1]) }}" class="{{ $isSelected ? 'on' : '' }}" @if($isSelected) aria-current="page" @endif>
                    <span class="child-pill-avatar">{{ mb_substr(data_get($k, 'name', 'ف'), 0, 1) }}</span>
                    <span class="child-pill-text">{{ data_get($k, 'name') }}</span>
                    @if($isSelected)
                        <span class="child-pill-indicator">فعال</span>
                    @endif
                </a>
            @endforeach
        </div>
    </nav>
@else
    <div class="parent-empty-state" role="status"><strong>هنوز فرزندی به حساب شما متصل نشده است.</strong><span>برای مشاهده گزارش‌های آموزشی، با آموزشگاه تماس بگیرید تا اتصال حساب بررسی شود.</span></div>
@endif
