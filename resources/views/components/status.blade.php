@props(['s'])
@php
$map = [
  'pending' => ['در انتظار ارسال', 'w'], 'review' => ['در حال بررسی', 'd'], 'graded' => ['بررسی شد', ''], 'late' => ['مهلت گذشته', 'r'],
  'upcoming' => ['به‌زودی', 'w'], 'ready' => ['آماده‌ی شروع', ''], 'done' => ['برگزار شد', 'd'],
  'present' => ['حاضر', ''], 'absent' => ['غایب', 'r'], 'tardy' => ['تأخیر', 'w'],
  'open' => ['در انتظار بررسی', 'w'], 'scheduled' => ['هماهنگ شد', ''],
];
[$label, $tone] = $map[$s] ?? [$s, ''];
@endphp
<x-tag :tone="$tone">{{ $label }}</x-tag>
