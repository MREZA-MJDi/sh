
@props([
'title',
'description' => null,
'eyebrow' => null,
'breadcrumbs' => [],
])

<section {{ $attributes->class([
    'border-b border-slate-200 bg-white'
]) }}>

    <x-kit.layout.container>

        <div class="py-10 sm:py-12 lg:py-14">

            @if(count($breadcrumbs))
                <div class="mb-7">
                    <x-kit.navigation.breadcrumbs :items="$breadcrumbs"/>
                </div>
            @endif

            <div class="max-w-3xl">

                @if($eyebrow)
                    <span class="mb-3 block text-xs font-extrabold tracking-wider text-indigo-600">
                        {{ $eyebrow }}
                    </span>
                @endif

                <h1 class="text-3xl font-black leading-tight tracking-tight text-slate-900 sm:text-4xl lg:text-5xl">
                    {{ $title }}
                </h1>

                @if($description)
                    <p class="mt-4 max-w-2xl text-sm leading-8 text-slate-600 sm:text-base">
                        {{ $description }}
                    </p>
                @endif

                @isset($actions)
                    <div class="mt-6 flex flex-wrap items-center gap-3">
                        {{ $actions }}
                    </div>
                @endisset

            </div>

        </div>

    </x-kit.layout.container>

</section>
