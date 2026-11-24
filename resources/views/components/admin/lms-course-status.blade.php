@props(['status'])
@php
  [$tone, $label] = match ($status) {
    'published' => ['ok', 'منتشرشده'],
    'draft' => ['warn', 'پیش‌نویس'],
    'archived' => ['mute', 'بایگانی'],
    default => ['mute', 'وضعیت نامشخص'],
  };
@endphp
<x-admin.badge :tone="$tone">{{ $label }}</x-admin.badge>
