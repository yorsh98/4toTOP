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

</x-app-layout>
