@props(['id', 'title', 'submitLabel' => 'ذخیره'])
<template id="{{ $id }}">
    <form method="{{ $method ?? 'POST' }}" action="{{ $action ?? '#' }}" {{ $attributes }}>
        @csrf
        {{ $slot }}
        <div class="admin-drawer-foot"><button class="panel-btn" type="submit">{{ $submitLabel }}</button><button class="panel-btn panel-btn-secondary" type="button" data-admin-close>انصراف</button></div>
    </form>
</template>
