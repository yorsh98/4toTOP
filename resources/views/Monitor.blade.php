<!DOCTYPE html>
<html lang="es" data-theme="dark">
  <head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Monitor de audiencias</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="icon" href="{{ asset('favicon.webp') }}" type="image/webp" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
    @stack('styles')
  </head>

  <body class="fo fo-kiosk">
    <div class="fo-backdrop" aria-hidden="true">
      <img src="{{ asset('img/centro-justicia-2560.webp') }}" alt="" decoding="async">
    </div>
    <header class="fo-kiosk-header">
      <div class="fo-kiosk-brand">
        <img src="{{ asset('img/pjud_blanco.webp') }}" alt="Poder Judicial" class="fo-only-dark" width="100">
        <img src="{{ asset('img/pjud_transparenteazul.webp') }}" alt="Poder Judicial" class="fo-only-light" width="100">
        <span>4º Tribunal de Juicio Oral en lo Penal de Santiago</span>
      </div>
      <div class="fo-kiosk-clock" x-data="{ time: '', date: '', init() { const tick = () => { const now = new Date(); this.time = new Intl.DateTimeFormat('es-CL', { timeZone: 'America/Santiago', hour: '2-digit', minute: '2-digit', hour12: false }).format(now); const d = new Intl.DateTimeFormat('es-CL', { timeZone: 'America/Santiago', weekday: 'long', day: 'numeric', month: 'long' }).format(now); this.date = d.charAt(0).toUpperCase() + d.slice(1); }; tick(); setInterval(tick, 1000); } }">
        <time x-text="time">{{ now()->format('H:i') }}</time>
        <span x-text="date">{{ ucfirst(now('America/Santiago')->translatedFormat('l, j \d\e F')) }}</span>
      </div>
    </header>
    <main class="fo-kiosk-main">
      <section class="fo-kiosk-panel fo-kiosk-video-panel" wire:ignore aria-label="Atención al público">
        <h1>Atención al Público</h1>
        <div class="fo-kiosk-video">
            <video
              id="player"
              class="w-full h-full object-contain [transform:translateZ(0)] [will-change:transform] [contain:paint] [backface-visibility:hidden]"
              playsinline
              autoplay
              loop
              muted
              controls
              controlslist="nodownload noplaybackrate"
              disablepictureinpicture>
              <source src="{{ asset('videos/VideoAtenciónPublico.mp4') }}" type="video/mp4"/>
            </video>
        </div>
        <div class="fo-kiosk-notice">
          <strong>Importante</strong>
          <p>Antes de ingresar a sala, debes registrarte en el mesón de <strong>Atención al Público</strong>.</p>
        </div>
      </section>
      <section class="fo-kiosk-panel fo-kiosk-program" aria-label="Programación de audiencias">
        <livewire:monitor-audiencias />
      </section>
    </main>
    @stack('scripts')
    <script>
      // Ping al servidor cada 5 minutos para mantener la sesión activa
      setInterval(() => {
        fetch('/csrf-refresh')
          .then((res) => res.json())
          .then((data) => {
            if (data.csrf) {
              document
                .querySelector('meta[name="csrf-token"]')
                .setAttribute('content', data.csrf);
            }
          });
      }, 5 * 60 * 1000); // 5 minutos

      // Watchdog del video (por si el navegador lo congela)
      document.addEventListener('DOMContentLoaded', () => {
        const v = document.getElementById('player');
        if (!v) return;

        let lastT = 0, stuck = 0;
        const tryPlay = () => v.play().catch(() => {});
        const recover = () => { try { v.load(); } catch(e){} tryPlay(); };

        v.addEventListener('ended', () => { v.currentTime = 0; tryPlay(); });

        setInterval(() => {
          const t = v.currentTime || 0;
          if (Math.abs(t - lastT) < 0.01 && !v.paused && !v.seeking) stuck++;
          else stuck = 0;
          lastT = t;

          if (stuck === 20) recover();        // ~20s sin avanzar
          if (stuck >= 40) location.reload(); // ~40s: recarga total
        }, 1000);

        document.addEventListener('visibilitychange', () => {
          if (!document.hidden) tryPlay();
        });
      });

      /**
       * AUTO-REFRESH (2 casos)
       * 1) Si el servidor responde 500 / 502 / 503 / 504 o falla el fetch -> reload
       * 2) Todos los días a las 08:00 (hora local del equipo) -> reload
       */

      // --- (A) Healthcheck para detectar caídas tipo 500 ---
      const HEALTHCHECK_URL = '/csrf-refresh'; // usa uno que siempre responda (idealmente 200)
      const HEALTHCHECK_EVERY_MS = 30 * 1000;  // cada 30s (ajusta si quieres)

      let healthFailStreak = 0;
      const MAX_FAILS_BEFORE_RELOAD = 2;       // 2 fallos seguidos -> recarga

      async function healthCheck() {
        try {
          const res = await fetch(HEALTHCHECK_URL, {
            method: 'GET',
            cache: 'no-store',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
          });

          // Si es un error 5xx, contamos fallo
          if (res.status >= 500) {
            healthFailStreak++;
          } else {
            healthFailStreak = 0;
          }

          // (opcional) si tu endpoint devuelve csrf, mantenemos el token
          // (esto conserva lo que ya hacías)
          if (res.ok) {
            const data = await res.json().catch(() => null);
            if (data?.csrf) {
              document.querySelector('meta[name="csrf-token"]')
                ?.setAttribute('content', data.csrf);
            }
          }

          if (healthFailStreak >= MAX_FAILS_BEFORE_RELOAD) {
            location.reload();
          }
        } catch (e) {
          // Si no hay respuesta (red caída / server abajo), también cuenta
          healthFailStreak++;
          if (healthFailStreak >= MAX_FAILS_BEFORE_RELOAD) {
            location.reload();
          }
        }
      }

      setInterval(healthCheck, HEALTHCHECK_EVERY_MS);
      // dispara uno al inicio para no esperar 30s
      healthCheck();


      // --- (B) Recarga diaria a las 08:00 AM ---
      function scheduleDailyReload(hour = 8, minute = 0) {
        const now = new Date();
        const next = new Date(now);
        next.setHours(hour, minute, 0, 0);

        // si ya pasó hoy, programa para mañana
        if (next <= now) next.setDate(next.getDate() + 1);

        const msUntil = next.getTime() - now.getTime();

        setTimeout(() => {
          location.reload();
          // por seguridad, vuelve a agendar (por si el reload no ocurre por alguna razón)
          scheduleDailyReload(hour, minute);
        }, msUntil);
      }

      scheduleDailyReload(8, 0);
    </script>
  </body>
</html>
