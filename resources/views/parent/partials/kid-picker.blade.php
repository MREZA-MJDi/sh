@if (($kids ?? collect())->isNotEmpty())
    <nav class="parent-child-picker" aria-label="انتخاب فرزند">
        @if ($kid)<input type="hidden" value="{{ $kid->id }}" data-selected-child>@endif
        <span class="parent-child-picker-label">نمایش اطلاعات:</span>
        @foreach (($kids ?? []) as $k)
            @php($isSelected = (int) data_get($k, 'id') === (int) data_get($kid ?? null, 'id'))
            <a href="{{ request()->fullUrlWithQuery(['child' => data_get($k, 'id'), 'page' => 1, 'homework' => 1, 'results' => 1, 'resources' => 1]) }}" class="{{ $isSelected ? 'on' : '' }}" @if($isSelected) aria-current="page" @endif>
                {{ data_get($k, 'name') }}
            </a>
        @endforeach
    </nav>
@else
    <div class="parent-empty-state" role="status"><strong>هنوز فرزندی به حساب شما متصل نشده است.</strong><span>برای مشاهده گزارش‌های آموزشی، با آموزشگاه تماس بگیرید تا اتصال حساب بررسی شود.</span></div>
@endif