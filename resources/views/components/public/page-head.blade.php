@props(['title', 'lead' => null, 'meta' => null])

{{-- Cabecera de las páginas públicas internas: volver al inicio, título y bajada --}}
<header {{ $attributes->class(['fo-page-head fo-list-head']) }}>
    <a href="{{ route('welcome') }}" class="fo-back" data-fo-link>
        <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Inicio
    </a>
    <h1 class="fo-page-title">{{ $title }}</h1>
    @if ($lead)
        <p class="fo-page-lead">{{ $lead }}</p>
    @endif
    @if ($meta)
        <p class="fo-list-meta">{{ $meta }}</p>
    @endif
    {{ $slot }}
</header>
