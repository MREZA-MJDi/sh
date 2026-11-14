
@props([
'title' => 'موردی برای نمایش وجود ندارد',
'description' => null,
'icon' => '✦',
'href' => null,
'action' => null,
])

<div {{ $attributes->class([
    'rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-5 py-12 text-center'
]) }}>

    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-indigo-50 text-2xl text-indigo-600">
        {{ $icon }}
    </div>

    <h3 class="mt-4 text-lg font-black text-slate-900">
        {{ $title }}
    </h3>

    @if($description)
        <p class="mx-auto mt-2 max-w-lg text-sm leading-7 text-slate-500">
            {{ $description }}
        </p>
    @endif

    @if($href && $action)
        <div class="mt-5">
            <x-kit.ui.button :href="$href">
                {{ $action }}
            </x-kit.ui.button>
        </div>
    @endif

</div>
