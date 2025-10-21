<x-app-layout>
    <link rel="stylesheet" href="{{ asset('css/audiencias.css') }}"><!-- si ya la usas para estilos base -->

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Panel administrativo Sistema Oficio / Libertades') }}
        </h2>
    </x-slot>

    {{-- Evita FOUC de Alpine --}}
    <style>[x-cloak]{display:none!important}</style>

    <div class="py-2" x-data="ofilibUI()" x-init="init()" x-cloak>
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">

                    

                    {{-- ====== MENÚ DE BOTONES (selección de módulo) ====== --}}
                    <template x-if="modo === 'menu'">
                        <div>
                            <p class="text-sm text-gray-600 mb-3">Selecciona el módulo:</p>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <button type="button" @click="open('oficios')"
                                        class="w-full px-4 py-3 rounded-xl border hover:bg-gray-50 text-left">
                                    <div class="font-semibold">OFICIOS</div>
                                    <div class="text-xs text-gray-500">PANEL ADMINISTRATIVO DE OFICIOS.</div>
                                </button>

                                <button type="button" @click="open('libertades')"
                                        class="w-full px-4 py-3 rounded-xl border hover:bg-gray-50 text-left">
                                    <div class="font-semibold">LIBERTADES</div>
                                    <div class="text-xs text-gray-500">PANEL ADMINISTRATIVO DE LIBERTADES.</div>
                                </button>
                            </div>
                        </div>
                    </template>

                    {{-- ====== CONTENIDO DEL MÓDULO ====== --}}
                    <template x-if="modo !== 'menu'">
                        <div>
                            <!-- barra superior con botón Volver -->
                            <div class="flex items-center justify-between mb-4">
                                <button type="button" @click="back()"
                                        class="inline-flex items-center gap-2 px-3 py-1.5 rounded-md border hover:bg-gray-50">
                                    <span>←</span> <span>Volver</span>
                                </button>
                                <div class="text-sm text-gray-500" x-text="tituloActual()"></div>
                            </div>

                            <!-- monta solo el componente activo -->
                            <div>
                                <template x-if="modo === 'oficios'">
                                    <livewire:oficio-tabla :wire:key="'oficios-full'" modo="full" />
                                </template>

                                <template x-if="modo === 'libertades'">
                                    <livewire:libertad-tabla :wire:key="'libertades-full'" modo="full" />
                                </template>
                            </div>
                        </div>
                    </template>

                </div>
            </div>
        </div>
    </div>

    {{-- Control UI con Alpine (no dupliques la importación de Alpine en assets) --}}
    <script>
        function ofilibUI(){
            return {
                // estado
                modo: 'menu',          // 'oficios' | 'libertades' | 'menu'
                topBarOpen: false,     // acordeón superior

                // init desde la URL
                init(){
                    const q = new URLSearchParams(location.search);
                    const p = q.get('tab');
                    const t = q.get('top'); // barra superior

                    if (['oficios','libertades'].includes(p)) this.modo = p;
                    if (t === '1' || t === 'true') this.topBarOpen = true;
                },

                // abrir módulo
                open(tab){
                    this.modo = tab;
                    this.updateURL();
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                },

                // volver a menú
                back(){
                    this.modo = 'menu';
                    this.updateURL();
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                },

                // toggle acordeón superior
                toggleTopBar(){
                    this.topBarOpen = !this.topBarOpen;
                    this.updateURL();
                },

                // sincroniza URL (?tab=&top=)
                updateURL(){
                    const url = new URL(location.href);

                    // tab
                    if (this.modo !== 'menu') url.searchParams.set('tab', this.modo);
                    else url.searchParams.delete('tab');

                    // top bar
                    if (this.topBarOpen) url.searchParams.set('top', '1');
                    else url.searchParams.delete('top');

                    history.replaceState({}, '', url);
                },

                // título del módulo actual
                tituloActual(){
                    return this.modo === 'oficios'    ? 'Módulo: Oficios'
                         : this.modo === 'libertades' ? 'Módulo: Libertades'
                         : '';
                }
            }
        }
    </script>

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
      x-show="$store.busy.visible"
      x-transition.opacity
      x-cloak
      class="fixed inset-0 z-[9999] bg-white/70 backdrop-blur-sm flex items-center justify-center"
      role="status" aria-live="polite" aria-busy="true"
    >
      <div class="flex flex-col items-center gap-3">
        <div class="w-7 h-7 rounded-full border-2 border-gray-400 border-t-transparent animate-spin"></div>
        <p class="text-sm text-gray-700">Cargando…</p>
      </div>

      <!-- Barra superior de progreso (decorativa) -->
      <div class="fixed top-0 left-0 right-0 h-0.5 overflow-hidden">
        <div class="h-full w-2/3 bg-gray-700" style="animation: progressbar 1.1s ease-in-out infinite;"></div>
      </div>
    </div>

    <script>
      // Store global "busy": cuenta requests simultáneas y maneja antiflicker
      document.addEventListener('alpine:init', () => {
        Alpine.store('busy', {
          pending: 1,       // empieza visible hasta que la página termine de cargar
          visible: true,
          _showTimer: null,

          on(){
            this.pending++;
            if(this._showTimer) return;
            // muestra si dura >120ms (evita parpadeo)
            this._showTimer = setTimeout(() => { this.visible = true; this._showTimer = null; }, 120);
          },
          off(){
            this.pending = Math.max(0, this.pending - 1);
            if(this.pending === 0){
              if(this._showTimer){ clearTimeout(this._showTimer); this._showTimer = null; }
              // espera 150ms para salir suave si vienen más requests
              setTimeout(() => { this.visible = false; }, 150);
            }
          }
        });
      });

      // Fin de carga de la página = libera un "pendiente"
      window.addEventListener('load', () => {
        try { Alpine.store('busy').off(); } catch(_) {}
      });

      // Hooks Livewire (v3). Activa/Desactiva loader en cada request
      document.addEventListener('livewire:load', () => {
        try {
          Livewire.hook('message.sent', () => Alpine.store('busy').on());
          Livewire.hook('message.failed', () => Alpine.store('busy').off());
          Livewire.hook('message.processed', () => Alpine.store('busy').off());
        } catch(_) {}
      });
    </script>
</x-app-layout>
