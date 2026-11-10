<header class="top">
    <button type="button" class="ib menu-btn" data-menu aria-controls="side" aria-expanded="false" aria-label="باز کردن منو"><x-icon name="menu"/></button>
    <h1 id="ttl">@yield('title', 'کارتابل')</h1>
    @if($portalRole === 'parent' && isset($children) && $children->isNotEmpty())
        <label class="sel">
            <span class="sr">انتخاب فرزند</span>
            <select data-child aria-label="انتخاب فرزند">
                @foreach($children as $child)
                    <option value="{{ $child->id }}" @selected($selected_child?->id === $child->id)>{{ $child->name }}@if($child->grade) · {{ $child->grade }}@endif</option>
                @endforeach
            </select>
        </label>
    @endif
    <button type="button" class="ib" data-theme-toggle aria-label="تغییر پوسته روشن و تیره" aria-pressed="false"><x-icon name="moon"/></button>
</header>
