<x-app-layout>
    <link rel="stylesheet" href="{{ asset('css/audiencias.css') }}">

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Administracion BD Audiencias') }}
        </h2>
    </x-slot>

    {{-- Evita FOUC de Alpine --}}
    <style>[x-cloak]{display:none!important}</style>

    <div class="py-2" x-data="audienciasUI()" x-init="init()" x-cloak>
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">

                    {{-- ====== BARRA DESPLEGABLE SUPERIOR: VISTA DIARIA ====== --}}
                    <div class="mb-4 border rounded-lg overflow-hidden">
                        <button type="button"
                                @click="toggleDiarias()"
                                class="w-full flex items-center justify-between px-4 py-3 bg-gray-50 hover:bg-gray-100">
                            <div class="flex items-center gap-2">
                                <span class="text-lg">📅</span>
                                <span class="font-semibold">Vista diaria de audiencias</span>
                                <span class="text-xs text-gray-500">(cambiar fecha, eliminar, etc.)</span>
                            </div>
                            <svg x-bind:class="diariasAbierta ? 'rotate-180' : ''"
                                 class="w-4 h-4 transition-transform" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd"
                                      d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.08z"
                                      clip-rule="evenodd" />
                            </svg>
                        </button>

                        {{-- Panel desplegable (ocupa la zona superior; empuja el resto hacia abajo) --}}
                        <div x-show="diariasAbierta"
                             x-transition.opacity
                             class="p-4 bg-white border-t">
                            <livewire:audiencias-diarias :wire:key="'aud-diarias'"/>
                        </div>
                    </div>

                    {{-- ====== MENÚ DE BOTONES (tus módulos) ====== --}}
                    <template x-if="modo === 'menu'">
                        <div>
                            <p class="text-sm text-gray-600 mb-3">Selecciona el módulo:</p>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <button type="button" @click="open('lecturas')"
                                        class="w-full px-4 py-3 rounded-xl border hover:bg-gray-50 text-left">
                                    <div class="font-semibold">Lecturas de Sentencia</div>
                                    <div class="text-xs text-gray-500">Carga el batch de lecturas.</div>
                                </button>

                                <button type="button" @click="open('cortas')"
                                        class="w-full px-4 py-3 rounded-xl border hover:bg-gray-50 text-left">
                                    <div class="font-semibold">Audiencias Cortas</div>
                                    <div class="text-xs text-gray-500">Carga el batch de cortas.</div>
                                </button>

                                <button type="button" @click="open('form')"
                                        class="w-full px-4 py-3 rounded-xl border hover:bg-gray-50 text-left">
                                    <div class="font-semibold">Continuacion/ Juicio Oral</div>
                                    <div class="text-xs text-gray-500">Formulario individual.</div>
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
                                <template x-if="modo === 'lecturas'">
                                    <livewire:lecturas-sentencia-batch :wire:key="'ls'"/>
                                </template>

                                <template x-if="modo === 'cortas'">
                                    <livewire:audiencias-cortas-batch :wire:key="'cortas'"/>
                                </template>

                                <template x-if="modo === 'form'">
                                    <livewire:audiencia-form :wire:key="'form'"/>
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
        function audienciasUI(){
            return {
                // estado
                modo: 'menu',          // lecturas | cortas | form | menu
                diariasAbierta: false, // acordeón superior

                // init desde la URL
                init(){
                    const q = new URLSearchParams(location.search);
                    const p = q.get('tab');
                    const d = q.get('diarias');

                    if (['lecturas','cortas','form'].includes(p)) this.modo = p;
                    if (d === '1' || d === 'true') this.diariasAbierta = true;
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

                // toggle acordeón de diarias
                toggleDiarias(){
                    this.diariasAbierta = !this.diariasAbierta;
                    this.updateURL();
                },

                // sincroniza URL (?tab=&diarias=)
                updateURL(){
                    const url = new URL(location.href);

                    // tab
                    if (this.modo !== 'menu') url.searchParams.set('tab', this.modo);
                    else url.searchParams.delete('tab');

                    // diarias
                    if (this.diariasAbierta) url.searchParams.set('diarias', '1');
                    else url.searchParams.delete('diarias');

                    history.replaceState({}, '', url);
                },

                // título del módulo actual
                tituloActual(){
                    return this.modo === 'lecturas' ? 'Lecturas de Sentencia'
                         : this.modo === 'cortas'   ? 'Audiencias Cortas'
                         : this.modo === 'form'     ? 'Audiencia (Formulario)'
                         : '';
                }
            }
        }
    </script>
</x-app-layout>
