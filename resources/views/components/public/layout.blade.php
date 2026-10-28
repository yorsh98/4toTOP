@props(['home' => false, 'title' => null])

@php
    $catalog = app(\App\Services\FrontOfficeCatalog::class);
    $sections = $catalog->sections();
    $sectionHref = fn (string $id) => $home ? "#{$id}" : route('welcome')."#{$id}";
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $title ? "{$title} · 4toTOP" : '4toTOP · 4º Tribunal de Juicio Oral en lo Penal de Santiago' }}</title>
        <link rel="icon" href="{{ asset('favicon.webp') }}" type="image/webp">

        {{-- Tema e intro antes del primer pintado, para que no parpadee --}}
        <script>
            (function () {
                var root = document.documentElement;
                root.classList.add('fo-js');
                try { root.dataset.theme = localStorage.getItem('fo-theme') === 'light' ? 'light' : 'dark'; } catch (e) {}
                @if ($home)
                try {
                    if (!sessionStorage.getItem('fo-intro') && location.hash.length < 2
                        && !matchMedia('(prefers-reduced-motion: reduce)').matches) {
                        root.classList.add('fo-intro');
                        sessionStorage.setItem('fo-intro', '1');
                    }
                } catch (e) {}
                @endif
                {{-- Apertura de módulos (resources/js/lib/app-open.js): cubre la pantalla con
                     el color de la capa antes del primer pintado para que la transición siga --}}
                try {
                    var key = function (href) {
                        var u = new URL(href, location.href);
                        return (u.pathname.replace(/\/+$/, '') || '/') + u.search + u.hash;
                    };
                    var nav = performance.getEntriesByType('navigation')[0];
                    var arrive = sessionStorage.getItem('ao-open');
                    var mode = null;
                    sessionStorage.removeItem('ao-open');
                    if (arrive && arrive === key(location.href)) mode = 'open';
                    @if ($home)
                    var last = sessionStorage.getItem('ao-last');
                    if (!mode && last && nav && nav.type !== 'reload'
                        && (nav.type === 'back_forward' || (document.referrer && key(document.referrer) === last))) {
                        mode = 'close';
                        root.dataset.aoFrom = last;
                        sessionStorage.removeItem('ao-last');
                    }
                    @endif
                    if (mode && !matchMedia('(prefers-reduced-motion: reduce)').matches) {
                        root.classList.add('ao-cover');
                        root.dataset.aoArrive = mode;
                        // La transición propia reemplaza al fundido nativo de @view-transition
                        addEventListener('pagereveal', function (e) {
                            if (!e.viewTransition) return;
                            e.viewTransition.ready.catch(function () {});
                            e.viewTransition.skipTransition();
                        }, { once: true });
                    }
                } catch (e) {}
            })();
        </script>
        {{-- Transición nativa entre páginas públicas (Chrome/Edge); el resto navega normal --}}
        <style>@view-transition { navigation: auto; }</style>
        <script type="speculationrules">
            {"prefetch": [{"where": {"selector_matches": "a[data-fo-link]"}, "eagerness": "moderate"}]}
        </script>

        @livewireStyles
        @stack('styles')
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" />
    </head>
    <body class="fo {{ $home ? 'fo-is-home' : 'fo-is-page' }}">
        <a class="fo-skip" href="#fo-main">Saltar al contenido</a>

        {{-- Fondo: Centro de Justicia de Santiago, se desplaza de izquierda a derecha con el scroll --}}
        <div class="fo-backdrop" aria-hidden="true">
            <img src="{{ asset('img/centro-justicia-2560.webp') }}"
                 srcset="{{ asset('img/centro-justicia-1280.webp') }} 1280w, {{ asset('img/centro-justicia-2560.webp') }} 2560w"
                 sizes="160vw" alt="" decoding="async" fetchpriority="low">
        </div>

        @if ($home)
            <div class="fo-preloader" aria-hidden="true">
                <svg class="fo-preloader-svg" viewBox="0 0 220 130" fill="none">
                    <path pathLength="1" d="M30 96 C 52 6, 150 -8, 160 62 C 166 104, 128 118, 116 92" />
                </svg>
                <div class="fo-preloader-word">
                    @foreach (str_split('4TOTOP') as $i => $char)
                        <span style="--i: {{ $i }}">{{ $char }}</span>
                    @endforeach
                </div>
            </div>
        @endif

        <header class="fo-header">
            <div class="fo-header-inner">
                <a href="{{ route('welcome') }}" class="fo-brand" data-fo-link aria-label="4toTOP, ir al inicio">
                    <img src="{{ asset('img/pjud_blanco.webp') }}" alt="" class="fo-brand-logo fo-only-dark" width="54" height="29">
                    <img src="{{ asset('img/pjud_transparenteazul.webp') }}" alt="" class="fo-brand-logo fo-only-light" width="54" height="29">
                    <span class="fo-brand-name">4toTOP</span>
                </a>

                <nav class="fo-nav cm-pill-track" aria-label="Secciones" data-fo-nav>
                    <span class="cm-pill fo-nav-pill" aria-hidden="true"></span>
                    @foreach ($sections as $id => $section)
                        <a href="{{ $sectionHref($id) }}" class="fo-nav-link" data-section="{{ $id }}" @unless ($home) data-fo-link @endunless>{{ $section['title'] }}</a>
                    @endforeach
                </nav>

                <div class="fo-header-actions">
                    <button type="button" class="fo-search-btn cm-press" data-fo-palette aria-keyshortcuts="Control+K Meta+K">
                        <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                        <span class="fo-search-btn-label">Buscar</span>
                        <kbd class="fo-kbd">Ctrl K</kbd>
                    </button>
                    <button type="button" class="fo-icon-btn cm-press" data-fo-theme aria-label="Cambiar a tema claro">
                        <i class="fa-solid fa-sun fo-only-dark" aria-hidden="true"></i>
                        <i class="fa-solid fa-moon fo-only-light" aria-hidden="true"></i>
                    </button>
                    @auth
                        <a href="{{ route('dashboard') }}" class="fo-cta cm-press">
                            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                            <span>Volver al backoffice</span>
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="fo-cta cm-press">
                            <i class="fa-solid fa-right-to-bracket" aria-hidden="true"></i>
                            <span>Ingresar</span>
                        </a>
                    @endauth
                </div>
            </div>
        </header>

        {{-- Sin data-app-shell: escalar la portada con sus tarjetas de vidrio (backdrop-filter) traba la apertura --}}
        <main id="fo-main" class="fo-main" tabindex="-1">
            {{ $slot }}
        </main>

        <footer class="fo-footer">
            <p>4º Tribunal de Juicio Oral en lo Penal de Santiago</p>
            <p>
                Fondo: Centro de Justicia de Santiago, foto de
                <a href="https://commons.wikimedia.org/wiki/File:Centro_de_Justicia,_Santiago_20230312.jpg" target="_blank" rel="noopener">Carlos Figueroa Rojas</a>,
                <a href="https://creativecommons.org/licenses/by-sa/4.0/deed.es" target="_blank" rel="noopener">CC BY-SA 4.0</a>
            </p>
            <p>
                Página desarrollada por Jorge Troncoso
                @guest
                    @if (Route::has('register'))
                        · <a href="{{ route('register') }}">Crear cuenta</a>
                    @endif
                @endguest
            </p>
        </footer>

        {{-- Buscador de accesos: también es el menú en pantallas chicas --}}
        <dialog class="fo-palette" id="fo-palette" aria-label="Buscar un acceso">
            <div class="fo-palette-box">
                <label class="fo-palette-field cm-focus-ring">
                    <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                    <span class="sr-only">Buscar un acceso</span>
                    <input type="search" placeholder="Buscar: audiencias, teléfonos, sentencias…" autocomplete="off"
                           role="combobox" aria-expanded="true" aria-controls="fo-palette-list" aria-autocomplete="list" data-fo-palette-input>
                    <kbd class="fo-kbd">Esc</kbd>
                </label>
                <ul class="fo-palette-list" id="fo-palette-list" role="listbox">
                    @foreach ($catalog->grouped() as $sectionId => $items)
                        <li class="fo-palette-group" role="presentation" data-fo-palette-group>
                            <span class="fo-palette-group-title">{{ $sections[$sectionId]['title'] }}</span>
                            <ul role="presentation">
                                @foreach ($items as $item)
                                    <li role="presentation">
                                        <a role="option" class="fo-palette-item"
                                           data-fo-palette-item
                                           data-text="{{ $item['title'] }} {{ $item['desc'] }} {{ $item['keywords'] }}"
                                           @if (isset($item['drawer']))
                                               href="{{ route('welcome') }}#{{ $item['drawer'] }}"
                                               @if ($home) data-fo-open="{{ $item['drawer'] }}" @endif
                                           @else
                                               href="{{ $item['href'] }}"
                                               @if (! empty($item['external'])) target="_blank" rel="noopener" @else data-fo-link @endif
                                           @endif>
                                            <i class="fa-solid {{ $item['icon'] }}" aria-hidden="true"></i>
                                            <span class="fo-palette-item-text">
                                                <strong>{{ $item['title'] }}</strong>
                                                <span>{{ $item['desc'] }}</span>
                                            </span>
                                            @if (! empty($item['external']))
                                                <i class="fa-solid fa-arrow-up-right-from-square fo-palette-ext" aria-label="Abre en otra pestaña"></i>
                                            @endif
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </li>
                    @endforeach
                </ul>
                <p class="fo-palette-empty" data-fo-palette-empty hidden>
                    Ningún acceso coincide con «<span data-fo-palette-query></span>». Prueba con otra palabra, como «audiencias» o «teléfono».
                </p>
                <p class="fo-palette-foot">
                    <span><kbd class="fo-kbd">↑</kbd><kbd class="fo-kbd">↓</kbd> moverse</span>
                    <span><kbd class="fo-kbd">Enter</kbd> abrir</span>
                    <span class="fo-palette-count" aria-live="polite"><b data-fo-palette-count>{{ count($catalog->items()) }}</b> <span data-fo-palette-noun>accesos</span></span>
                </p>
            </div>
        </dialog>

        {{ $drawers ?? '' }}

        @stack('scripts')
        @livewireScripts
    </body>
</html>
