<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Sistema Oficio/libertades 4to TOP Stgo.') }}</title>
        <link rel="icon" href="{{ asset('favicon.png') }}" type="image/png">

        <!-- Fonts --> 
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
       <!-- STILOS ICONOS --> 
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
        @livewireStyles
        @stack('styles')
        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-gray-100 text-gray-900">
        <div class="min-h-screen bg-gray-100 lg:flex">
            <livewire:layout.navigation />

            <div class="min-w-0 flex-1">
                <!-- Page Heading -->
                @if (isset($header))
                    <header class="bg-white shadow">
                        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
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

<style>
  [x-cloak]{display:none!important}
  @keyframes progressbar {
    0%   { transform: translateX(-100%); }
    50%  { transform: translateX(-40%); }
    100% { transform: translateX(0%); }
  }
</style>

<!-- Overlay a pantalla completa -->
<div
  x-data
  x-show="$store.busy && $store.busy.visible"
  x-transition.opacity
  x-cloak
  class="fixed inset-0 z-[9999] bg-white/70 backdrop-blur-sm flex items-center justify-center"
  role="status" aria-live="polite" aria-busy="true"
>
  <div class="flex flex-col items-center gap-3">
    <div class="w-7 h-7 rounded-full border-2 border-gray-400 border-t-transparent animate-spin"></div>
    <p class="text-sm text-gray-700">Cargando...</p>
  </div>

  <!-- Barra superior de progreso (decorativa) -->
  <div class="fixed top-0 left-0 right-0 h-0.5 overflow-hidden">
    <div class="h-full w-2/3 bg-gray-700" style="animation: progressbar 1.1s ease-in-out infinite;"></div>
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

    var bindLivewireBusyHooks = function () {
      if (window.__busyHooksBound || typeof window.Livewire === 'undefined') {
        return;
      }

      window.__busyHooksBound = true;

      Livewire.hook('message.sent', () => {
        try {
          initBusyStore();
          window.Alpine.store('busy').on();
        } catch (_) {}
      });

      Livewire.hook('message.failed', turnBusyOff);
      Livewire.hook('message.processed', turnBusyOff);
    };

    initBusyStore();
    document.addEventListener('alpine:init', initBusyStore);
    if (document.readyState === 'complete' || document.readyState === 'interactive') {
      turnBusyOff();
    } else {
      window.addEventListener('load', turnBusyOff, { once: true });
    }

    document.addEventListener('livewire:navigated', turnBusyOff);
    bindLivewireBusyHooks();
    document.addEventListener('livewire:load', bindLivewireBusyHooks);
  })();

  // (Opcional) Si también haces cargas manuales con Alpine (como tu switch de módulos),
  // puedes envolverlas así:
  // Alpine.store('busy').on(); ... trabajo ... Alpine.store('busy').off();
</script>

    </body>
</html>
