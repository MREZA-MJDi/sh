
@props([
'title' => null,
'description' => null,
'padding' => true,
'interactive' => false,
])

<div {{ $attributes->class([
    'rounded-2xl border border-slate-200 bg-white shadow-sm',
    $padding ? 'p-5 sm:p-6' : '',
    $interactive ? 'transition duration-200 hover:-translate-y-1 hover:shadow-lg' : '',
]) }}>

    @if($title || $description || isset($actions))

        <div class="mb-5 flex flex-wrap items-start justify-between gap-4">

            <div>
                @if($title)
                    <h3 class="text-lg font-black text-slate-900">
                        {{ $title }}
                    </h3>
                @endif

                @if($description)
                    <p class="mt-2 text-sm leading-7 text-slate-500">
                        {{ $description }}
                    </p>
                @endif
            </div>

            @isset($actions)
                <div class="shrink-0">
                    {{ $actions }}
                </div>
            @endisset

        </div>

    @endif

    {{ $slot }}

</div>
