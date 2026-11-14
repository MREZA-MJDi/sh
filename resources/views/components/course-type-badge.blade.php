@props(['type'])
<span style="background:{{ $type->color() }}1a;color:{{ $type->color() }};padding:2px 10px;border-radius:999px;font-size:.85em">
    {{ $type->icon() }} {{ $type->label() }}
</span>
<small style="color:#64748b">{{ $type->hint() }}</small>
