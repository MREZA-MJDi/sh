@extends('teacher.layouts.app')

@section('title', 'کلاس‌ها و فیلم‌ها')

@section('content')
<h2>کلاس‌ها و فیلم‌ها</h2>
<div class="g g2">
    <section class="c"><h3>آپلود فیلم کلاس</h3>
        <form method="POST" action="{{ url('teacher/classes/videos') }}" enctype="multipart/form-data" data-upload>
            @csrf
            <label class="drop"><input type="file" name="video" accept="video/*"><span data-drop-text>فیلم را اینجا بکشید یا انتخاب کنید</span></label>
            <button class="b" type="submit" disabled>آپلود فیلم</button>
        </form>
    </section>

    <section class="c"><h3>لینک کلاس آنلاین</h3>
        @forelse (($links ?? []) as $l)
            <div class="row"><div>{{ data_get($l, 'title') }}<small>{{ data_get($l, 'schedule') }}</small></div>
                @if (data_get($l, 'room'))<i class="bd gn">فعال</i>
                @else<button type="button" class="b s" data-modal="links" data-action="{{ url('teacher/links/'.data_get($l, 'id')) }}" data-method="PUT" data-values="{{ json_encode(['title' => data_get($l, 'title'), 'provider' => data_get($l, 'provider'), 'schedule' => data_get($l, 'schedule'), 'room' => ''], JSON_UNESCAPED_UNICODE) }}">افزودن</button>
                @endif
            </div>
        @empty
            <p>کلاسی ثبت نشده است.</p>
        @endforelse
    </section>

    <section class="c"><h3>فیلم‌های ضبط‌شده</h3>
        @forelse (($videos ?? []) as $v)
            <div class="row"><div>{{ data_get($v, 'title') }}<small>{{ data_get($v, 'duration') }}</small></div>
                @if (data_get($v, 'published'))<i class="bd gn">منتشر شد</i>@else<i class="bd am">پیش‌نویس</i>@endif
            </div>
        @empty
            <p>فیلمی آپلود نشده است.</p>
        @endforelse
    </section>

    <section class="c"><h3>منابع و جزوه‌ها</h3>
        @forelse (($resources ?? []) as $r)
            <div class="row"><div>{{ data_get($r, 'title') }}<small>{{ data_get($r, 'type') }}</small></div>
                @include('teacher.partials.crud-actions', ['entity' => 'resources', 'item' => $r])
            </div>
        @empty
            <p>موردی ثبت نشده است.</p>
        @endforelse
        <br><button type="button" class="b" data-modal="resources" data-action="{{ url('teacher/resources') }}" data-method="POST">+ منبع جدید</button>
    </section>
</div>
@endsection
