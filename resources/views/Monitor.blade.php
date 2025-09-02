<!DOCTYPE html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Monitor de audiencias</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="/css/app.css">
    <link rel="icon" href="{{ asset('favicon.webp') }}" type="image/webp" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
    @stack('styles')
  </head>

  <body class="bg-gray-100 flex flex-col min-h-screen">
    <x-headerSist />

    <main class="flex flex-1 gap-2 p-2 pb-6">
      <!-- Panel izquierdo (VIDEO + aviso fijo abajo) -->
      <div
        class="w-1/3 bg-white rounded-lg shadow-lg overflow-hidden border border-gray-200"
        wire:ignore>
        <div class="h-full p-4 flex flex-col">
          <!-- Título panel -->
          <div class="text-center mb-4 bg-white rounded-xl shadow-md p-4 relative overflow-hidden">
            <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-blue-500 via-green-500 to-purple-600"></div>
            <h1 class="text-2xl font-bold text-gray-800 mb-2 flex items-center justify-center gap-3">
              <i class="fas fa-hands-helping"></i>
              ATENCIÓN DE PÚBLICO
              <i class="fas fa-comments"></i>
            </h1>
          </div>

          <!-- VIDEO ocupa todo el espacio disponible -->
          <div class="flex-1 flex items-center justify-center w-full bg-black rounded-md">
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

          <!-- AVISO FIJO: ocupa el “footer” del panel izquierdo -->
          <div
            class="mt-3 bg-white/90 text-gray-800 font-semibold text-center rounded-xl shadow-2xl border-2 border-red-600 px-4 py-3" aria-live="polite" aria-atomic="true">
            <div class="flex items-center justify-center gap-3 text-sm">
              <i class="fas fa-bell text-red-600 text-lg"></i>
              <span class="leading-tight">
                ⚠️ <strong>Importante:</strong> Antes de ingresar a sala, debes
                registrarte en el mesón de <span class="text-red-600 font-bold">Atención al Público</span>.
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Panel derecho -->
      <div class="w-2/3 bg-white rounded-lg shadow-lg overflow-hidden border border-gray-200">
        <div class="h-full p-4 flex flex-col">
          <div class="text-center mb-4 bg-white rounded-xl shadow-md p-4 relative overflow-hidden">
            <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-blue-500 via-green-500 to-purple-600"></div>
            <h1 class="text-2xl font-bold text-gray-800 mb-2 flex items-center justify-center gap-3">
              <i class="fas fa-balance-scale"></i>
              PROGRAMACIÓN DE AUDIENCIAS
              <i class="far fa-calendar-alt"></i>
            </h1>
          </div>

          <!-- Contenedor para Livewire -->
          <div class="flex">
            <livewire:monitor-audiencias />
          </div>
        </div>
      </div>
    </main>

    <!-- Footer general del layout (sigue igual) -->
    <footer class="footer-Monitor">
      <a href="" class="footer-button-Monitor">
        PROGRAMACIÓN DE AUDIENCIAS -
        {{ strtoupper(\Carbon\Carbon::now()->isoFormat('dddd DD-MM-YYYY')) }}
      </a>
    </footer>
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
    </script>
  </body>
</html>
