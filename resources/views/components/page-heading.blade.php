@props(['eyebrow' => null, 'title', 'width' => 'max-w-7xl'])

<div {{ $attributes->merge(['class' => "mx-auto flex w-full {$width} items-center justify-between gap-4 px-4 sm:px-6 lg:px-8"]) }}>
    <div>
        @if ($eyebrow)
            <p class="font-mono text-[10px] font-bold uppercase tracking-[0.2em] text-sky-700">{{ $eyebrow }}</p>
        @endif
        <h2 @class(['text-xl font-semibold leading-tight text-slate-800', 'mt-0.5' => $eyebrow])>{{ $title }}</h2>
    </div>

    @if (isset($actions))
        <div class="shrink-0">{{ $actions }}</div>
    @endif
</div>
