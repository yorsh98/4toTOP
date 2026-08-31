<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $code }} | {{ config('app.name', '4toTOP') }}</title>
        <link rel="icon" href="{{ asset('favicon.png') }}" type="image/png">
        <style>
            :root { color-scheme: light; }
            * { box-sizing: border-box; }
            body { min-width: 320px; margin: 0; color: #172033; font-family: Figtree, Arial, sans-serif; background: #f4f7fc; }
            .error-page { position: relative; display: grid; min-height: 100vh; place-items: center; padding: 2rem; overflow: hidden; isolation: isolate; }
            .error-page::before { position: absolute; z-index: -2; inset: 0; background: var(--background-image) center / cover no-repeat; content: ''; }
            .error-page::after { position: absolute; z-index: -1; inset: 0; background: linear-gradient(90deg, rgba(244, 247, 252, .08), rgba(244, 247, 252, .78) 42%, rgba(244, 247, 252, .96)); content: ''; }
            .record { width: min(100%, 44rem); padding: clamp(2rem, 6vw, 4.5rem); border: 1px solid rgba(10, 46, 93, .15); border-radius: 1.5rem; background: rgba(255, 255, 255, .88); box-shadow: 0 1.5rem 4rem rgba(10, 46, 93, .18); }
            .identity { display: flex; align-items: center; gap: 1rem; color: #0a2e5d; font-size: .73rem; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; }
            .identity img { width: 9rem; height: auto; max-height: 3.25rem; object-fit: contain; object-position: left center; }
            .divider { width: 100%; height: 1px; margin: 1.75rem 0; background: #b7cce5; }
            .code { margin: 0; color: #0a2e5d; font-size: clamp(6rem, 20vw, 11rem); font-weight: 700; line-height: .78; letter-spacing: -.09em; }
            h1 { max-width: 34rem; margin: 1.5rem 0 .65rem; color: #172033; font-size: clamp(1.5rem, 4vw, 2.4rem); line-height: 1.08; }
            p { max-width: 34rem; margin: 0; color: #4a5a70; font-size: 1rem; line-height: 1.6; }
            .actions { display: flex; flex-wrap: wrap; gap: .75rem; margin-top: 2rem; }
            .button { display: inline-flex; min-height: 2.75rem; align-items: center; justify-content: center; padding: .75rem 1rem; border: 1px solid #0a2e5d; border-radius: .55rem; color: #fff; font-size: .95rem; font-weight: 700; text-decoration: none; background: #0a2e5d; transition: background .15s ease, transform .15s ease; }
            .button:hover { background: #123e74; transform: translateY(-1px); }
            .button:focus-visible { outline: 3px solid #5688c4; outline-offset: 3px; }
            .button--secondary { color: #0a2e5d; background: transparent; }
            .button--secondary:hover { color: #fff; }
            .footer { margin-top: 3rem; color: #64748b; font-size: .75rem; letter-spacing: .08em; text-transform: uppercase; }
            @media (max-width: 640px) { .error-page { align-items: end; padding: 1rem; } .error-page::after { background: rgba(244, 247, 252, .9); } .record { border-radius: 1rem; } .identity { align-items: flex-start; flex-direction: column; gap: .75rem; } }
            @media (prefers-reduced-motion: reduce) { .button { transition: none; } }
        </style>
    </head>
    <body>
        <main class="error-page" style="--background-image: url('{{ asset('img/blue.webp') }}');">
            <section class="record" aria-labelledby="error-title">
                <div class="identity">
                    <img src="{{ asset('img/pjud_transparenteazul.webp') }}" alt="Poder Judicial de Chile">
                    <span>Registro de navegación · HTTP {{ $code }}</span>
                </div>

                <div class="divider"></div>

                <p class="code" aria-hidden="true">{{ $code }}</p>
                <h1 id="error-title">{{ $title }}</h1>
                <p>{{ $message }}</p>

                <div class="actions">
                    <a class="button" href="{{ route('welcome') }}">Volver al inicio</a>
                    @if (($showLogin ?? false) && Route::has('login'))
                        <a class="button button--secondary" href="{{ route('login') }}">Iniciar sesión</a>
                    @endif
                </div>

                <p class="footer">4º Tribunal de Juicio Oral en lo Penal de Santiago</p>
            </section>
        </main>
    </body>
</html>
