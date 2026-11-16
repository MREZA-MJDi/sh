<div class="sel">
    @foreach (($kids ?? []) as $k)
        <a href="?kid={{ data_get($k, 'id') }}" class="{{ data_get($k, 'id') == data_get($kid ?? null, 'id') ? 'on' : '' }}">{{ data_get($k, 'name') }}</a>
    @endforeach
</div>
