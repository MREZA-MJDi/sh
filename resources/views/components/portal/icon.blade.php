@props(['name' => 'folder'])
<svg {{ $attributes->merge(['class' => 'portal-icon']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
 @switch($name)
 @case('home') <path d="m3 10 9-7 9 7v10h-6v-6H9v6H3z"/> @break
 @case('calendar') <rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18M8 15h2m4 0h2"/> @break
 @case('video') <rect x="3" y="5" width="18" height="14" rx="3"/><path d="m10 9 5 3-5 3z"/> @break
 @case('message') <path d="M21 11.5a8.5 8.5 0 0 1-8.5 8.5H4l1.5-4A8.5 8.5 0 1 1 21 11.5Z"/><path d="M8 11h8m-8 4h5"/> @break
 @case('file') <path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9zM14 3v6h6M8 13h8m-8 4h6"/> @break
 @case('book') <path d="M12 6v15M12 6C8 3 5 3 3 4v15c3-1 6-1 9 2 3-3 6-3 9-2V4c-2-1-5-1-9 2z"/> @break
 @case('check') <rect x="4" y="3" width="16" height="18" rx="2"/><path d="m8 12 3 3 5-6"/> @break
 @case('chart') <path d="M4 3v17h17M8 16v-5m5 5V6m5 10v-8"/> @break
 @case('user') <circle cx="12" cy="7" r="4"/><path d="M4 21v-2a8 8 0 0 1 16 0v2"/> @break
 @case('wallet') <rect x="3" y="5" width="18" height="15" rx="3"/><path d="M3 10h18M15 15h3"/> @break
 @case('clock') <circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/> @break
 @case('back') <path d="m9 6 6 6-6 6"/> @break
 @case('plus') <path d="M12 5v14M5 12h14"/> @break
 @default <path d="M3 7a2 2 0 0 1 2-2h5l2 2h7a2 2 0 0 1 2 2v10H3z"/>
 @endswitch
</svg>
