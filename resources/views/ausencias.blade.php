<x-in-layout>
                        <div class="grid gap-6 pt-5 mb-6 lg:grid-cols-1 lg:gap-8 animate__animated animate__flipInX animate__delay-0.5s">
                            <div class="absolute top-4 left-4">
                                <a href="{{ url('/') }}" class="bg-white text-black font-semibold py-1 px-3 rounded-md shadow-md flex items-center text-sm hover:bg-blue-700 hover:text-white transition">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"></path>
                                    </svg>
                                    INICIO
                                </a>
                            </div>
                            <div class="flex justify-center items-start gap-4 rounded-lg bg-white p-6 shadow-[0px_14px_34px_0px_rgba(0,0,0,0.08)] ring-1 ring-white/[0.05] transition duration-300 hover:text-black/70 hover:ring-black/20 focus:outline-none focus-visible:ring-[#FF2D20] lg:pb-10 dark:bg-zinc-900 dark:ring-zinc-800 dark:hover:text-white/70 dark:hover:ring-zinc-700 dark:focus-visible:ring-[#FF2D20]">
                                <div class="flex size-12 shrink-0 items-center justify-center rounded-full sm:size-16">    
                                <!-- Calendar X (multicolor) -->
                                <svg width="60" height="60" viewBox="0 0 24 24" role="img" aria-label="Calendario con X"
                                    xmlns="http://www.w3.org/2000/svg">
                                <!-- Sombra suave -->
                                    <rect x="2" y="3" width="20" height="18" rx="3" fill="#0F172A" opacity="0.08"/>
                                    <!-- Cuerpo del calendario -->
                                    <rect x="2" y="3" width="20" height="18" rx="3" fill="#FFFFFF"/>
                                    <!-- Cabecera -->
                                    <rect x="2" y="3" width="20" height="5" rx="3" fill="#E11D48"/>
                                    <!-- Aros (anillas) -->
                                    <rect x="6" y="2" width="2" height="3" rx="1" fill="#334155"/>
                                    <rect x="16" y="2" width="2" height="3" rx="1" fill="#334155"/>

                                    <!-- Líneas guía de la grilla (decorativo) -->
                                    <path d="M4 10.5H20" stroke="#E5E7EB" stroke-width="1"/>
                                    <path d="M4 14.5H20" stroke="#E5E7EB" stroke-width="1"/>

                                    <!-- La X (usa currentColor para que puedas cambiarla con Tailwind) -->
                                    <g transform="translate(0,1)">
                                        <path d="M9 9 L15 15 M15 9 L9 15" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                    </g>

                                    <!-- Borde -->
                                    <rect x="2" y="3" width="20" height="18" rx="3" fill="none" stroke="#CBD5E1"/>
                                </svg>

                                </div>
                                <div class="pt-3 sm:pt-5">
                                    <h2 class="text-xl font-semibold text-black dark:text-white">Ausentismo</h2>
                                </div>
                            </div>
                        </div>
                        <div class="grid gap-6 lg:grid-cols-1 lg:gap-8 animate__animated animate__flipInX animate__delay-0.8s">
                            <div class="flex flex-col gap-6 overflow-hidden rounded-lg bg-white p-6 shadow-[0px_14px_34px_0px_rgba(0,0,0,0.08)] ring-1 ring-white/[0.05] lg:p-10 lg:pb-10 dark:bg-zinc-900 dark:ring-zinc-800">
                                   <livewire:ausentismo-calendario/> 
                            </div>
                        </div>
</x-in-layout>