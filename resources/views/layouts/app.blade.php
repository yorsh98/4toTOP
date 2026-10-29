<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ isset($title) ? $title.' · 4toTOP' : '4toTOP · Backoffice' }}</title>
        <link rel="icon" href="{{ asset('favicon.webp') }}" type="image/webp">
        <script>
            try {
                document.documentElement.dataset.theme = localStorage.getItem('fo-theme') === 'dark' ? 'dark' : 'light';
            } catch (_) {
                document.documentElement.dataset.theme = 'light';
            }
        </script>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
        @livewireStyles
        @stack('styles')
        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bo antialiased">
        <div class="bo-shell min-h-screen lg:flex">
            <livewire:layout.navigation />

            <div class="min-w-0 flex-1">
                <!-- Page Heading -->
                @if (isset($header))
                    <header class="bo-header h-16">
                        <div class="flex h-full items-center">
                            {{ $header }}
                        </div>
                    </header>
                @endif

                <!-- Page Content -->
                <main>
                    {{ $slot }}
                </main>
            </div>
        </div>
        @stack('scripts')
        @livewireScripts

        {{-- ===== Loader Global (visible en carga inicial y durante requests Livewire) ===== --}}



<!-- Overlay a pantalla completa -->
<div
  x-data
  x-show="$store.busy && $store.busy.visible"
  x-transition.opacity
  x-cloak
  class="bo-loader fixed inset-0 z-[9999] flex items-center justify-center"
  role="status" aria-live="polite" aria-busy="true"
>
  <div class="flex flex-col items-center gap-3">
    <div class="bo-loader-spinner w-7 h-7 rounded-full border-2 animate-spin"></div>
    <p class="text-sm">Cargando...</p>
  </div>

  <!-- Barra superior de progreso (decorativa) -->
  <div class="fixed top-0 left-0 right-0 h-0.5 overflow-hidden">
    <div class="bo-loader-progress h-full w-2/3"></div>
  </div>
</div>

<script>
  (function () {
    var initBusyStore = function () {
      var alpine = window.Alpine;
      if (!alpine) {
        return;
      }

      if (alpine.store('busy')) {
        return;
      }

      alpine.store('busy', {
        pending: 0,
        visible: false,
        _showTimer: null,

        on() {
          this.pending++;
          if (this._showTimer) {
            return;
          }

          this._showTimer = setTimeout(() => {
            this.visible = true;
            this._showTimer = null;
          }, 120);
        },
        off() {
          this.pending = Math.max(0, this.pending - 1);

          if (this.pending === 0) {
            if (this._showTimer) {
              clearTimeout(this._showTimer);
              this._showTimer = null;
            }

            setTimeout(() => {
              this.visible = false;
            }, 150);
          }
        },
      });
    };

    var turnBusyOff = function () {
      try {
        initBusyStore();
        window.Alpine.store('busy').off();
      } catch (_) {}
    };

    initBusyStore();
    document.addEventListener('alpine:init', initBusyStore);
    if (document.readyState === 'complete' || document.readyState === 'interactive') {
      turnBusyOff();
    } else {
      window.addEventListener('load', turnBusyOff, { once: true });
    }

    document.addEventListener('livewire:navigating', () => {
      try {
        initBusyStore();
        window.Alpine.store('busy').on();
      } catch (_) {}
    });
    document.addEventListener('livewire:navigated', turnBusyOff);
  })();

  // (Opcional) Si también haces cargas manuales con Alpine (como tu switch de módulos),
  // puedes envolverlas así:
  // Alpine.store('busy').on(); ... trabajo ... Alpine.store('busy').off();
</script>

    </body>
</html>
