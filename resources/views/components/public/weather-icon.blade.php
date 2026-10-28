@props(['kind' => 'cloudy', 'day' => true])

@php
    $cloud = 'M46 44H19a11 11 0 1 1 2.1-21.8A14 14 0 0 1 47.6 24 10 10 0 0 1 46 44z';
    $sunRays = collect(range(0, 7))->map(fn ($i) => $i * 45);
@endphp

<svg {{ $attributes->class(['fo-wx-icon', "fo-wx-icon--{$kind}"]) }} viewBox="0 0 64 64" fill="none" aria-hidden="true"
     stroke-linecap="round" stroke-linejoin="round">
    @if ($kind === 'clear')
        @if ($day)
            <g class="fo-wx-rays" style="transform-origin: 32px 32px">
                @foreach ($sunRays as $angle)
                    <line x1="32" y1="9" x2="32" y2="14" transform="rotate({{ $angle }} 32 32)" class="fo-wx-sun" stroke-width="3" />
                @endforeach
            </g>
            <circle cx="32" cy="32" r="11" class="fo-wx-sun fo-wx-sun-core" stroke-width="3" />
        @else
            <g class="fo-wx-moon" style="transform-origin: 32px 32px">
                <path d="M38 13a17 17 0 1 0 13 27A14 14 0 0 1 38 13z" class="fo-wx-moon-shape" stroke-width="3" />
            </g>
            <path d="M16 16v6M13 19h6" class="fo-wx-star" stroke-width="2.4" />
            <path d="M50 50v4M48 52h4" class="fo-wx-star" stroke-width="2" style="animation-delay: 1.1s" />
        @endif
    @elseif ($kind === 'partly')
        @if ($day)
            <g class="fo-wx-rays" style="transform-origin: 22px 22px">
                @foreach ($sunRays as $angle)
                    <line x1="22" y1="6" x2="22" y2="9.5" transform="rotate({{ $angle }} 22 22)" class="fo-wx-sun" stroke-width="2.6" />
                @endforeach
            </g>
            <circle cx="22" cy="22" r="8" class="fo-wx-sun fo-wx-sun-core" stroke-width="2.6" />
        @else
            <path d="M25 9a12 12 0 1 0 9 19A10 10 0 0 1 25 9z" class="fo-wx-moon-shape" stroke-width="2.6" />
        @endif
        <path d="{{ $cloud }}" class="fo-wx-cloud fo-wx-cloud--front" stroke-width="3" transform="translate(4 6)" />
    @elseif ($kind === 'cloudy')
        <path d="{{ $cloud }}" class="fo-wx-cloud fo-wx-cloud--back" stroke-width="2.6" transform="translate(9 -4) scale(.8)" />
        <path d="{{ $cloud }}" class="fo-wx-cloud fo-wx-cloud--front" stroke-width="3" transform="translate(-2 6)" />
    @else
        <path d="{{ $cloud }}" class="fo-wx-cloud fo-wx-cloud--front" stroke-width="3" transform="translate(0 -6)" />
        @if ($kind === 'fog')
            @foreach ([[14, 46, 0], [20, 52, .6], [12, 58, 1.2]] as [$x, $y, $delay])
                <line x1="{{ $x }}" y1="{{ $y }}" x2="{{ $x + 36 }}" y2="{{ $y }}" class="fo-wx-fogline" stroke-width="3" style="animation-delay: {{ $delay }}s" />
            @endforeach
        @elseif ($kind === 'snow')
            @foreach ([[19, 0], [32, .8], [45, 1.6]] as [$x, $delay])
                <g class="fo-wx-flake" style="animation-delay: {{ $delay }}s; transform-origin: {{ $x }}px 48px">
                    <path d="M{{ $x }} 44.5v7M{{ $x - 3 }} 46.25l6 3.5M{{ $x - 3 }} 49.75l6-3.5" class="fo-wx-snow" stroke-width="2" />
                </g>
            @endforeach
        @else
            @php($drops = $kind === 'storm' ? [[20, 0], [46, .6]] : [[22, 0], [32, .4], [42, .8]])
            @foreach ($drops as [$x, $delay])
                <line x1="{{ $x }}" y1="44" x2="{{ $x - 3 }}" y2="51" class="fo-wx-drop" stroke-width="3" style="animation-delay: {{ $delay }}s" />
            @endforeach
            @if ($kind === 'storm')
                <path d="M35 40l-5 8h6l-4 8" class="fo-wx-bolt" stroke-width="3" />
            @endif
        @endif
    @endif
</svg>
