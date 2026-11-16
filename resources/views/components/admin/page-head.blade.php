{{-- Page header: category-coloured icon chip, title, description, actions (default slot) --}}
@props(['title', 'desc' => null, 'icon' => 'home', 'tone' => 'home', 'backUrl' => null, 'backLabel' => 'بازگشت'])
<header class="adm-pagehead" data-tone="{{ $tone }}">
  <div class="adm-pagehead-main">
    <span class="adm-pagehead-ico"><x-admin.icon :name="$icon"/></span>
    <div>
      @if ($backUrl)<a class="adm-back" href="{{ $backUrl }}"><x-admin.icon name="chev" style="transform:rotate(-90deg)"/>{{ $backLabel }}</a>@endif
      <h1>{{ $title }}</h1>
      @if ($desc)<p>{{ $desc }}</p>@endif
    </div>
  </div>
  <div class="adm-actions">{{ $slot }}</div>
</header>
