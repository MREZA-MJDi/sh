@php
    $modals = [
        'links' => [
            ['title', 'نام کلاس'],
            ['provider', 'سرویس', ['skyroom' => 'اسکای‌روم', 'bbb' => 'BigBlueButton', 'link' => 'لینک دستی']],
            ['schedule', 'زمان‌بندی'],
            ['room', 'شناسه اتاق یا لینک'],
        ],
        'homework' => [
            ['title', 'عنوان تکلیف'],
            ['type', 'نوع', ['PDF' => 'PDF', 'متن' => 'متن']],
            ['due', 'مهلت'],
        ],
        'resources' => [
            ['title', 'عنوان'],
            ['type', 'نوع', ['PDF' => 'PDF', 'کتاب' => 'کتاب', 'جزوه' => 'جزوه']],
        ],
    ];
@endphp
@foreach ($modals as $name => $fields)
<div class="md" data-modal-box="{{ $name }}">
    <form class="mf" method="POST" action="">
        @csrf
        <input type="hidden" name="_method" value="PUT" disabled>
        <h3 data-title>افزودن</h3>
        @foreach ($fields as $f)
            <label>{{ $f[1] }}
                @if (isset($f[2]))
                    <select name="{{ $f[0] }}">@foreach ($f[2] as $val => $text)<option value="{{ $val }}">{{ $text }}</option>@endforeach</select>
                @else
                    <input name="{{ $f[0] }}" required>
                @endif
            </label>
        @endforeach
        <div class="send"><button class="b" type="submit">ذخیره</button><button type="button" class="b s" data-close>انصراف</button></div>
    </form>
</div>
@endforeach
