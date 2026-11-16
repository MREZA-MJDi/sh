@props(['name', 'small' => false])
<span {{ $attributes->merge(['class' => 'adm-avatar'.($small ? ' is-sm' : '')]) }} data-c="{{ crc32($name) % 5 }}">{{ mb_substr($name, 0, 1) }}</span>
