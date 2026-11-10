<header class="top">
  <button type="button" class="ib menu-btn" data-menu aria-label="باز کردن منو"><x-icon name="menu"/></button>
  <h1>@yield('title', 'کارتابل')</h1>
  @if($role === 'parent')
    <label class="kid"><span class="sr">انتخاب فرزند</span>
      <select data-child>
        @foreach($m['children'] as $k)
          <option value="{{ $k['id'] }}" @selected($k['id'] === $child['id'])>{{ $k['name'] }} · {{ $k['grade'] }}</option>
        @endforeach
      </select>
    </label>
  @endif
  <a class="ib" href="{{ $rt('news.index') }}" aria-label="اخبار مهم"><x-icon name="bell"/><i>{{ fa(count($m['news'])) }}</i></a>
  <button type="button" class="ib" data-theme-toggle aria-label="تغییر پوسته‌ی روشن/تیره"><x-icon name="moon"/></button>
  <a class="ib pr" href="{{ $role === 'student' ? route('student.profile.show') : route('parent.dashboard') }}" aria-label="حساب کاربری"><x-icon name="user"/></a>
</header>
