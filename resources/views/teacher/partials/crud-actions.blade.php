{{-- دکمه‌های ویرایش/حذف. ورودی: $entity (links|homework|resources) و $item --}}
@php
    $keys = [
        'links'     => ['title', 'provider', 'schedule', 'room'],
        'homework'  => ['title', 'type', 'due'],
        'resources' => ['title', 'type'],
    ][$entity];
    $vals = [];
    foreach ($keys as $k) { $vals[$k] = data_get($item, $k); }
    $endpoint = url('teacher/'.$entity.'/'.data_get($item, 'id'));
@endphp
<button type="button" class="b s" data-modal="{{ $entity }}" data-action="{{ $endpoint }}" data-method="PUT" data-values="{{ json_encode($vals, JSON_UNESCAPED_UNICODE) }}">ویرایش</button>
<form method="POST" action="{{ $endpoint }}" class="inline">@csrf @method('DELETE')<button type="button" class="b s" style="color:var(--rd)" data-delete>حذف</button></form>
