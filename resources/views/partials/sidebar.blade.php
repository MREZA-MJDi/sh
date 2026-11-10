@php
$nav = $role === 'student' ? [
  [null, [['خانه', 'dashboard', 'home'], ['اخبار مهم', 'news.index', 'bell']]],
  ['یادگیری', [['دوره‌های من', 'courses.index', 'book'], ['کلاس‌های من', 'classes.index', 'video'], ['ویدیوهای ضبط‌شده', 'videos.index', 'play'], ['تکالیف', 'assignments.index', 'task'], ['آزمون‌ها', 'exams.index', 'exam'], ['جزوات و منابع', 'resources.index', 'file'], ['کتاب‌ها و محصولات', 'shop.index', 'cart']]],
  ['ارتباط', [['پیام‌ها', 'messages.index', 'msg']]],
  ['عملکرد و حساب', [['نتایج و عملکرد', 'results.index', 'chart'], ['حضور و غیاب', 'attendance.index', 'cal'], ['یادداشت‌ها', 'notes.index', 'note'], ['پروفایل', 'profile.show', 'user']]],
] : [
  [null, [['خانه', 'dashboard', 'home'], ['اخبار مهم', 'news.index', 'bell']]],
  ['وضعیت فرزند', [['پیشرفت دوره‌ها و کلاس‌ها', 'courses.index', 'book'], ['تکالیف و نتیجه‌ی بررسی', 'assignments.index', 'task'], ['نتایج آزمون‌ها', 'exams.index', 'exam'], ['حضور و غیاب', 'attendance.index', 'cal'], ['منابع و محصولات', 'resources.index', 'file']]],
  ['ارتباط', [['درخواست مشاوره', 'consult.index', 'phone'], ['ارسال پیام', 'messages.index', 'msg']]],
];
$active = fn ($r) => request()->routeIs($r === 'dashboard' ? "$role.dashboard" : "$role." . explode('.', $r)[0] . '.*')
    || ($r === 'resources.index' && request()->routeIs("$role.shop.*"));
$next = collect($m['schedule'])->firstWhere('live', true);
@endphp
<aside class="side" id="side" aria-label="منوی اصلی">
  <div class="me">
    <div class="av"><svg viewBox="0 0 120 120" width="100%" height="100%" aria-hidden="true"><use href="#avatar"/></svg></div>
    <h3>{{ $user['name'] }}</h3>
    <small>{{ $role === 'student' ? 'دانش‌آموز · ' . $user['grade'] : 'ولی · ' . $child['name'] }}</small>
  </div>
  <div class="role" role="group" aria-label="تغییر کارتابل">
    <a href="{{ route('student.dashboard') }}" @class(['on' => $role === 'student']) @if($role === 'student') aria-current="true" @endif>دانش‌آموز</a>
    <a href="{{ route('parent.dashboard') }}" @class(['on' => $role === 'parent']) @if($role === 'parent') aria-current="true" @endif>اولیاء</a>
  </div>
  <nav class="nav" aria-label="کارتابل {{ $role === 'student' ? 'دانش‌آموز' : 'اولیاء' }}">
    @foreach($nav as [$group, $links])
      @if($group)<h6>{{ $group }}</h6>@endif
      @foreach($links as [$label, $r, $icon])
        <a href="{{ $rt($r) }}" @class(['on' => $active($r)]) @if($active($r)) aria-current="page" @endif><x-icon :name="$icon"/>{{ $label }}</a>
      @endforeach
    @endforeach
  </nav>
  <div class="prem">
    @if($role === 'student')
      <h4>کلاس بعدی</h4>
      <p>{{ collect($m['courses'])->firstWhere('id', $next['course_id'])['title'] }} · امروز {{ $next['time'] }}</p>
      <a class="btn s" href="{{ route('student.classes.index') }}">ورود به اسکای‌روم</a>
    @else
      <h4>نیاز به مشاوره دارید؟</h4>
      <p>با منشی آقای شیخان هماهنگ کنید.</p>
      <a class="btn s" href="{{ route('parent.consult.create') }}">درخواست جلسه</a>
    @endif
    <form method="POST" action="{{ route('logout') }}">@csrf<button class="logout" type="submit">خروج از حساب</button></form>
  </div>
</aside>
