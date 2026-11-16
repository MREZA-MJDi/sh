<div class="sel" aria-label="انتخاب فرزند">
    @foreach (($kids ?? []) as $k)
        <a href="{{ request()->url() }}?child={{ data_get($k, 'id') }}" class="{{ data_get($k, 'id') == data_get($kid ?? null, 'id') ? 'on' : '' }}">{{ data_get($k, 'name') }}</a>
    @endforeach
</div>
