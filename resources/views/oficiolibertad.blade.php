<x-app-layout>

    <x-slot name="header">
        <x-page-heading eyebrow="Oficios y libertades · Administración" title="Panel administrativo" />
    </x-slot>

    {{-- Evita FOUC de Alpine --}}

    <div class="py-2" x-data="ofilibUI()" x-init="init()" x-cloak>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bo-card">
                <div class="p-4 sm:p-6">

                    

                    {{-- ====== MENÚ DE BOTONES (selección de módulo) ====== --}}
                    <template x-if="modo === 'menu'">
                        <div>
                            <p class="text-sm bo-muted mb-3">Selecciona el módulo:</p>

                            <div class="bo-segmented grid grid-cols-1 md:grid-cols-2 gap-2 p-2">
                                <button type="button" @click="open('oficios')"
                                        class="bo-segmented-item w-full px-4 py-3 text-left">
                                    <div class="font-semibold">OFICIOS</div>
                                    <div class="text-xs bo-muted">PANEL ADMINISTRATIVO DE OFICIOS.</div>
                                </button>

                                <button type="button" @click="open('libertades')"
                                        class="bo-segmented-item w-full px-4 py-3 text-left">
                                    <div class="font-semibold">LIBERTADES</div>
                                    <div class="text-xs bo-muted">PANEL ADMINISTRATIVO DE LIBERTADES.</div>
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
                                        class="bo-btn bo-btn--secondary">
                                    <span>←</span> <span>Volver</span>
                                </button>
                                <div class="text-sm bo-muted" x-text="tituloActual()"></div>
                            </div>

                            <nav class="bo-segmented flex flex-wrap gap-2 p-2 mb-4" aria-label="Módulos">
                                <button type="button" class="bo-segmented-item px-4 py-2" @click="open('oficios')" :class="{ 'is-active': modo === 'oficios' }" :aria-current="modo === 'oficios' ? 'page' : null">OFICIOS</button>
                                <button type="button" class="bo-segmented-item px-4 py-2" @click="open('libertades')" :class="{ 'is-active': modo === 'libertades' }" :aria-current="modo === 'libertades' ? 'page' : null">LIBERTADES</button>
                            </nav>

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
