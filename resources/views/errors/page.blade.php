<!DOCTYPE html>
<html lang="es" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $code }} | {{ config('app.name', '4toTOP') }}</title>
    <link rel="icon" href="{{ asset('favicon.webp') }}" type="image/webp">
    <script>try { document.documentElement.dataset.theme = localStorage.getItem('fo-theme') === 'light' ? 'light' : 'dark'; } catch (e) {}</script>
    <style>
        :root {

    /* Paleta */
    --fo-toga: #0A2E5D;
    --fo-signal: #2D8CFF;
    --fo-signal-strong: #5AA5FF;
    --fo-alarm: #FF2D20;
    --fo-bg: #050B18;
    --fo-surface: #0B1630;
    --fo-surface-2: #12224A;
    --fo-line: rgba(150, 180, 230, .14);
    --fo-line-strong: rgba(150, 180, 230, .28);
    --fo-fg: #E8EEF7;
    --fo-mut: #93A1BA;
    --fo-on-signal: #04101F;
    --fo-chrome: linear-gradient(180deg, #F4F7FC 0%, #B7C3D9 48%, #4E5D7A 100%);
    --fo-spot: #FFFFFF;
    --fo-wash: rgba(10, 46, 93, .55);
    --fo-glass: rgba(11, 22, 48, .58);
    --fo-glass-strong: rgba(11, 22, 48, .86);
    --fo-glass-hover: rgba(22, 42, 86, .62);
    --fo-glass-edge: rgba(190, 215, 255, .14);
    --fo-glass-blur: blur(18px) saturate(150%);
    --fo-photo-filter: grayscale(1) contrast(1.15) brightness(.62);
    --fo-photo-tint: #1B5BB8;
    --fo-sun: #FFC24A;
    --fo-moon: #DCE6F7;
    --fo-photo-wash: linear-gradient(90deg, rgba(5, 11, 24, .45), rgba(5, 11, 24, 0) 62%), linear-gradient(180deg, rgba(5, 11, 24, .35) 0%, rgba(5, 11, 24, .72) 45%, rgba(5, 11, 24, .9) 100%);

    /* Tipografía */
    --fo-display: "Archivo Variable", "Archivo", system-ui, sans-serif;
    --fo-sans: "Public Sans Variable", "Public Sans", system-ui, -apple-system, "Segoe UI", sans-serif;
    --fo-mono: "Red Hat Mono Variable", "Red Hat Mono", ui-monospace, monospace;

    /* Movimiento: curvas del portafolio, una sola fuente para chat-motion */
    --fo-ease: cubic-bezier(.2, .8, .2, 1);
    --fo-ease-io: cubic-bezier(.76, 0, .24, 1);
    --cm-ease-out: var(--fo-ease);
    --cm-accent: var(--fo-signal);
    --cm-accent-soft: color-mix(in srgb, var(--fo-signal) 22%, transparent);
    --cm-ok: var(--fo-alarm);
    --cm-muted: var(--fo-mut);
    --cm-fg: var(--fo-fg);
    --cm-line: var(--fo-line);
    --cm-shadow: 0 1px 0 rgba(255, 255, 255, .06) inset;

    --fo-radius: 14px;
    --fo-radius-sm: 9px;
    --fo-gutter: clamp(16px, 4vw, 40px);
    --fo-max: 1240px;
    --fo-max-padded: calc(var(--fo-max) + var(--fo-gutter) * 2);


            --fo-display: system-ui, -apple-system, "Segoe UI", sans-serif;
            --fo-sans: system-ui, -apple-system, "Segoe UI", sans-serif;
        }
        :root[data-theme="light"] {

    --fo-signal: #1F6FE0;
    --fo-signal-strong: #0A55C2;
    --fo-alarm: #D91E12;
    --fo-bg: #EEF2F8;
    --fo-surface: #FFFFFF;
    --fo-surface-2: #E2E9F4;
    --fo-line: rgba(10, 46, 93, .12);
    --fo-line-strong: rgba(10, 46, 93, .26);
    --fo-fg: #0A1A33;
    --fo-mut: #4C5A72;
    --fo-on-signal: #FFFFFF;
    --fo-chrome: linear-gradient(180deg, #0A2E5D 0%, #164A8C 55%, #6E8BB5 100%);
    --fo-spot: #2D8CFF;
    --fo-wash: rgba(45, 140, 255, .12);
    --fo-glass: rgba(255, 255, 255, .58);
    --fo-glass-strong: rgba(255, 255, 255, .9);
    --fo-glass-hover: rgba(255, 255, 255, .78);
    --fo-glass-edge: rgba(255, 255, 255, .85);
    --fo-photo-filter: grayscale(1) contrast(.95) brightness(1.12);
    --fo-photo-tint: #2D8CFF;
    --fo-sun: #E08A00;
    --fo-moon: #0A2E5D;
    --fo-photo-wash: linear-gradient(90deg, rgba(238, 242, 248, .62), rgba(238, 242, 248, 0) 62%), linear-gradient(180deg, rgba(238, 242, 248, .45) 0%, rgba(238, 242, 248, .74) 45%, rgba(238, 242, 248, .9) 100%);
    --cm-shadow: 0 1px 2px rgba(10, 26, 51, .06), 0 8px 24px rgba(10, 26, 51, .06);
        }
        * { box-sizing: border-box; }
        body { margin: 0; background: var(--fo-bg); color: var(--fo-fg); font-family: var(--fo-sans); line-height: 1.55; }
        .backdrop { position: fixed; inset: 0; overflow: hidden; z-index: -1; background: var(--fo-bg); }
        .backdrop img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; filter: var(--fo-photo-filter); }
        .backdrop::before, .backdrop::after { content: ""; position: absolute; inset: 0; }
        .backdrop::before { z-index: 1; background: var(--fo-photo-tint); mix-blend-mode: color; opacity: .85; }
        .backdrop::after { z-index: 2; background: var(--fo-photo-wash); }
        main { min-height: 100vh; display: grid; place-items: center; padding: 1rem; }
        .record { width: min(100%, 44rem); padding: clamp(1.5rem, 6vw, 4rem); border: 1px solid var(--fo-glass-edge); border-radius: var(--fo-radius); background: var(--fo-glass-strong); backdrop-filter: var(--fo-glass-blur); -webkit-backdrop-filter: var(--fo-glass-blur); }
        .identity { display: flex; align-items: center; flex-wrap: wrap; gap: 1rem; font-size: .8rem; font-weight: 700; }
        .identity img { width: 9rem; height: auto; }
        .light-logo { display: none; }
        [data-theme="light"] .dark-logo { display: none; }
        [data-theme="light"] .light-logo { display: block; }
        .divider { height: 1px; margin: 1.75rem 0; background: var(--fo-line-strong); }
        .code { margin: 0; color: var(--fo-fg); font-size: clamp(6rem, 20vw, 11rem); font-weight: 800; line-height: .85; letter-spacing: -.07em; background: var(--fo-chrome); background-clip: text; -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        h1 { margin: 1.5rem 0 .65rem; font-size: clamp(1.5rem, 4vw, 2.4rem); line-height: 1.15; }
        p { margin: 0; }
        .actions { display: flex; flex-wrap: wrap; gap: .75rem; margin-top: 2rem; }
        .button { display: inline-flex; min-height: 2.75rem; align-items: center; justify-content: center; padding: .7rem 1rem; border: 1px solid var(--fo-signal); border-radius: var(--fo-radius-sm); color: var(--fo-on-signal); background: var(--fo-signal); font: inherit; font-weight: 700; text-decoration: none; cursor: pointer; }
        .button:hover { background: var(--fo-signal-strong); }
        .button--secondary { border-color: var(--fo-line-strong); color: var(--fo-fg); background: transparent; }
        .button--secondary:hover { background: var(--fo-glass-hover); }
        .button[hidden] { display: none; }
        :focus-visible { outline: 3px solid var(--fo-signal); outline-offset: 3px; }
        .footer { margin-top: 3rem; font-size: .8rem; }
        @media (prefers-reduced-motion: reduce) { *, *::before, *::after { animation: none !important; transition: none !important; } }
    </style>
</head>
<body>
    <div class="backdrop" aria-hidden="true">
        <img src="{{ asset('img/centro-justicia-2560.webp') }}" srcset="{{ asset('img/centro-justicia-1280.webp') }} 1280w, {{ asset('img/centro-justicia-2560.webp') }} 2560w" sizes="100vw" alt="">
    </div>
    <main>
        <section class="record" aria-labelledby="error-title">
            <div class="identity">
                <img class="dark-logo" src="{{ asset('img/pjud_blanco.webp') }}" alt="Poder Judicial de Chile">
                <img class="light-logo" src="{{ asset('img/pjud_transparenteazul.webp') }}" alt="Poder Judicial de Chile">
                <span>Registro de navegación · HTTP {{ $code }}</span>
            </div>
            <div class="divider"></div>
            <p class="code" aria-hidden="true">{{ $code }}</p>
            <h1 id="error-title">{{ $title }}</h1>
            <p>{{ $message }}</p>
            <div class="actions">
                <a class="button" href="{{ route('welcome') }}">Volver al inicio</a>
                <button class="button button--secondary" type="button" onclick="history.back()" id="back" hidden>Volver atrás</button>
                @if ($showLogin ?? false)
                    <a class="button button--secondary" href="{{ route('login') }}">Iniciar sesión</a>
                @endif
            </div>
            <p class="footer">4º Tribunal de Juicio Oral en lo Penal de Santiago</p>
        </section>
    </main>
    <script>if (history.length > 1) document.getElementById('back').hidden = false;</script>
</body>
</html>
