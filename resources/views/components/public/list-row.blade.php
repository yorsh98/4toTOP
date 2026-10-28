@props([
    'title',
    'href' => null,
    'meta' => null,
    'icon' => null,
    'badges' => [],
    'keywords' => '',
    'inlineBadges' => false,
])

@php
    $external = $href && (str_starts_with($href, 'http') || str_starts_with($href, '/storage/'));
    $tag = $href ? 'a' : 'div';
@endphp

<li class="fo-list-item" data-fo-item data-text="{{ $title }} {{ $meta }} {{ $keywords }}" data-reveal="seq">
    <{{ $tag }}
        @if ($href) href="{{ $href }}" @if ($external) target="_blank" rel="noopener" @endif @endif
        {{ $attributes->class(['fo-row', 'fo-row--link cm-lift cm-press' => $href]) }}>
        @if ($icon)
            <span class="fo-row-icon" aria-hidden="true"><i class="fa-solid {{ $icon }}"></i></span>
        @endif
        <span class="fo-row-text">
            <span class="fo-row-title">{{ $title }}</span>
            @if ($inlineBadges)
                <span class="fo-row-meta-line">
                    @if ($meta) <span class="fo-row-meta">{{ $meta }}</span> @endif
                    @foreach ($badges as $badge)
                        <span class="fo-badge {{ $badge['class'] ?? '' }}" @isset($badge['title']) title="{{ $badge['title'] }}" @endisset>{{ $badge['label'] }}</span>
                    @endforeach
                </span>
            @elseif ($meta)
                <span class="fo-row-meta">{{ $meta }}</span>
            @endif
        </span>
        @foreach ($inlineBadges ? [] : $badges as $badge)
            <span class="fo-badge {{ $badge['class'] ?? '' }}" @isset($badge['title']) title="{{ $badge['title'] }}" @endisset>{{ $badge['label'] }}</span>
        @endforeach
        @if ($href)
            <i class="fa-solid {{ $external ? 'fa-arrow-up-right-from-square' : 'fa-chevron-right' }} fo-row-cue"
               @if ($external) aria-label="Abre en otra pestaña" @else aria-hidden="true" @endif></i>
        @endif
    </{{ $tag }}>
</li>
