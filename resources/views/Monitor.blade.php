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
      rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
    @stack('styles')
  </head>

  <body class="bg-gray-100 flex flex-col min-h-screen">
    <x-headerSist />

    <main class="flex flex-1 gap-2 p-2 pb-6">
      <!-- Panel izquierdo (VIDEO) -->
      <div
        class="w-1/3 bg-white rounded-lg shadow-lg overflow-hidden border border-gray-200"
        wire:ignore
      >
        <div class="h-full p-4 flex flex-col">
          <div class="text-center mb-4 bg-white rounded-xl shadow-md p-4 relative overflow-hidden">
            <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-blue-500 via-green-500 to-purple-600"></div>
            <h1 class="text-2xl font-bold text-gray-800 mb-2 flex items-center justify-center gap-3">
              <i class="fas fa-hands-helping"></i>
              ATENCIÓN DE PÚBLICO
              <i class="fas fa-comments"></i>
            </h1>
          </div>

          <!-- Contenedor para el video -->
          <div class="flex items-center justify-center w-full h-full bg-black">
            <video
              id="player"
              class="w-full h-full object-contain [transform:translateZ(0)] [will-change:transform] [contain:paint] [backface-visibility:hidden]"
              playsinline
              autoplay
              loop
              muted
              controls
              controlslist="nodownload noplaybackrate"
              disablepictureinpicture
            >
              <source
                src="{{ asset('videos/VideoAtenciónPublico.mp4') }}"
                type="video/mp4"
              />
            </video>
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

    <footer class="footer-Monitor">
      <a href="" class="footer-button-Monitor">
        PROGRAMACIÓN DE AUDIENCIAS -
        {{ strtoupper(\Carbon\Carbon::now()->isoFormat('dddd DD-MM-YYYY')) }}
      </a>
    </footer>

    <!-- NOTIFICACIÓN (solo Tailwind) -->
    <div
      id="notification"
      class="fixed top-4 left-1/2 -translate-x-1/2 z-50 pointer-events-none
             opacity-0 -translate-y-2 scale-95
             transition-all duration-300 ease-out"
      aria-live="polite"
      aria-atomic="true"
    >
      <div
        class="bg-white/90 text-gray-800 font-semibold text-center
               rounded-xl shadow-2xl border-2 border-red-600
               px-6 py-4 max-w-4xl mx-auto"
      >
        <div class="flex items-center justify-center gap-3 text-base">
          <i class="fas fa-bell text-red-600 text-xl"></i>
          <span class="leading-tight text-red-600">
            ⚠️ <strong>Importante:</strong> Antes de ingresar a sala, debes
            registrarte en el mesón de <span class="font-bold">Atención al Público</span>.
          </span>
        </div>
      </div>
    </div>

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

      // Animación SOLO con Tailwind (toggle de clases) + Watchdog de video
      document.addEventListener('DOMContentLoaded', () => {
        const notice = document.getElementById('notification');
        const v = document.getElementById('player');

        // --- Ciclo: 20s (5s visible + 15s oculta), solo cambiando clases Tailwind ---
        const showNotice = () => {
          // aparece: quita clases de oculto y aplica ease-out
          notice.classList.remove('opacity-0', '-translate-y-2', 'scale-95');
          notice.classList.add('opacity-100', 'translate-y-0', 'scale-100');
        };
        const hideNotice = () => {
          // desaparece: agrega clases de oculto
          notice.classList.add('opacity-0', '-translate-y-2', 'scale-95');
          notice.classList.remove('opacity-100', 'translate-y-0', 'scale-100');
        };

        // arranca oculto; cada 20s la mostramos 5s
        hideNotice();
        setInterval(() => {
          showNotice();
          setTimeout(hideNotice, 5000);
        }, 20000);

        // --- Watchdog del video (suave) ---
        if (v) {
          let lastT = 0, stuck = 0;
          const tryPlay = () => v.play().catch(() => {});
          const recover = () => { try { v.load(); } catch(e){} tryPlay(); };

          v.addEventListener('ended', () => { v.currentTime = 0; tryPlay(); });

          setInterval(() => {
            const t = v.currentTime || 0;
            if (Math.abs(t - lastT) < 0.01 && !v.paused && !v.seeking) stuck++;
            else stuck = 0;
            lastT = t;

            if (stuck === 20) recover();   // ~20s sin avanzar
            if (stuck >= 40) location.reload(); // ~40s: recarga total
          }, 1000);

          document.addEventListener('visibilitychange', () => {
            if (!document.hidden) tryPlay();
          });
        }
      });
    </script>
  </body>
</html>
