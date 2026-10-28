@props(['item', 'variant' => 'row'])

@php
    $external = ! empty($item['external']);
    $tag = isset($item['drawer']) ? 'button' : 'a';
@endphp

<{{ $tag }}
    @if ($tag === 'button')
        type="button" data-fo-open="{{ $item['drawer'] }}" aria-haspopup="dialog"
    @else
        href="{{ $item['href'] }}"
        @if ($external) target="_blank" rel="noopener" @else data-fo-link data-app-open @endif
    @endif
    {{ $attributes->class(['fo-access', "fo-access--{$variant}", 'cm-lift', 'cm-press']) }}
    data-reveal="seq">
    @if ($variant === 'shot' && isset($item['image']))
        <span class="fo-access-shot">
            <img src="{{ asset($item['image']) }}" alt="" loading="lazy" decoding="async">
        </span>
    @endif
    <span class="fo-access-icon" aria-hidden="true" data-app-icon><i class="fa-solid {{ $item['icon'] }}"></i></span>
    <span class="fo-access-text">
        <span class="fo-access-title">{{ $item['title'] }}</span>
        <span class="fo-access-desc">{{ $item['desc'] }}</span>
    </span>
    @if ($external)
        <i class="fa-solid fa-arrow-up-right-from-square fo-access-cue" aria-label="Abre en otra pestaña"></i>
    @elseif ($tag === 'button')
        <i class="fa-solid fa-chevron-right fo-access-cue" aria-hidden="true"></i>
    @endif
    {{ $slot }}
</{{ $tag }}>
