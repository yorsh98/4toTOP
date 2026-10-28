@props(['eyebrow' => null, 'title', 'width' => 'max-w-7xl'])

<div {{ $attributes->merge(['class' => "mx-auto flex w-full {$width} items-center justify-between gap-4 px-4 sm:px-6 lg:px-8"]) }}>
    <div>
        @if ($eyebrow)
            <p class="bo-eyebrow">{{ $eyebrow }}</p>
        @endif
        <h2 @class(['bo-heading-title', 'mt-0.5' => $eyebrow])>{{ $title }}</h2>
    </div>

    @if (isset($actions))
        <div class="shrink-0">{{ $actions }}</div>
    @endif
</div>
