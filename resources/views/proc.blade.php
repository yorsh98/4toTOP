<x-in-layout>
    @php
        $procedimientos = [
            [
                'url' => '/documentos/procedimientos/Procedimiento Control de causas LVI (Plan de Intervención Individual).pdf',
                'nombre' => 'Procedimiento Control de causas LVI (Plan de Intervención Individual)',                
            ],
            [
                'url' => '/documentos/procedimientos/Procedimiento Control de Asistencia.pdf',
                'nombre' => 'Procedimiento Control de Asistencia',                
            ],
            [
                'url' => '/documentos/procedimientos/Procedimiento Agendamiento de Causas.pdf',
                'nombre' => 'Procedimiento Agendamiento de Causas',                
            ],
            [
                'url' => '/documentos/procedimientos/Procedimiento Uso de las Tarjetas BIP .pdf',
                'nombre' => 'Procedimiento Uso de las Tarjetas BIP ',
            ],
            [
                'url' => '/documentos/procedimientos/Procedimiento Turnos 4 TOP .pdf',
                'nombre' => 'Procedimiento Turnos 4to TOP ',
            ],
            [
                'url' => '/documentos/procedimientos/Procedimiento Tramitacion Licencias Medicas.pdf',
                'nombre' => 'Procedimiento Tramitacion Licencias Medicas',                
            ],
            [
                'url' => '/documentos/procedimientos/Procedimiento Tramitacion de Profugos.pdf',
                'nombre' => 'Procedimiento Tramitacion de Profugos',                
            ],
            [
                'url' => '/documentos/procedimientos/Procedimiento Tramitación de Notificaciones.pdf',
                'nombre' => 'Procedimiento Tramitación de Notificaciones',            
            ],
            [
                'url' => '/documentos/procedimientos/Procedimiento Toma de Inventarios Parciales.pdf',
                'nombre' => 'Procedimiento Toma de Inventarios Parciales',                
            ],
            [
                'url' => '/documentos/procedimientos/Procedimiento Presentacion de TT o PP con Ordenes de Arresto.pdf',
                'nombre' => 'Procedimiento Presentacion de TT o PP con Ordenes de Arresto',                
            ],
            [
                'url' => '/documentos/procedimientos/Procedimiento Planilla de Ausencias del Personal.pdf',
                'nombre' => 'Procedimiento Planilla de Ausencias del Personal',                
            ],
            [
                'url' => '/documentos/procedimientos/Procedimiento Plan Contingencia Unidad de Testigos y Peritos.pdf',
                'nombre' => 'Procedimiento Plan Contingencia Unidad de Testigos y Peritos',                
            ],
            [
                'url' => '/documentos/procedimientos/Procedimiento Funciones de Jefe de Turno 1 y 2.pdf',
                'nombre' => 'Procedimiento Funciones de Jefe de Turno 1 y 2',                
            ],
            [
                'url' => '/documentos/procedimientos/Procedimiento Entrega de Cheques Cuenta Jurisdiccional.pdf',
                'nombre' => 'Procedimiento Entrega de Cheques Cuenta Jurisdiccional',                
            ],
            [
                'url' => '/documentos/procedimientos/Procedimiento Empleo de la Sala de Entrevista a Menores.pdf',
                'nombre' => 'Procedimiento Empleo de la Sala de Entrevista a Menores',                
            ],
            [
                'url' => '/documentos/procedimientos/Procedimiento Ejecución de Sentencias.pdf',
                'nombre' => 'Procedimiento Ejecución de Sentencias',                
            ],
            [
                'url' => '/documentos/procedimientos/Procedimiento Denuncia de Accidentes del Trabajo y Enfermedades Profesionales.pdf',
                'nombre' => 'Procedimiento Denuncia de Accidentes del Trabajo y Enfermedades Profesionales',                
            ],
            [
                'url' => '/documentos/procedimientos/Procedimiento Custodia de Especies.pdf',
                'nombre' => 'Procedimiento Custodia de Especies',                
            ],
            [
                'url' => '/documentos/procedimientos/Procedimiento Controles de Detención Presentación Voluntaria.pdf',
                'nombre' => 'Procedimiento Controles de Detención Presentación Voluntaria',
            ],
            //si necesita agregar otro procedimiento copiar desde y hasta [] para poder generar un nuevo modulo en la vista
        ];
    @endphp



                        <div class="grid gap-6 pt-5 mb-6 lg:grid-cols-1 lg:gap-8 animate__animated animate__zoomInDown animate__delay-0.5s">
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
                                    <svg viewBox="0 0 1024 1024" class="icon" version="1.1" xmlns="http://www.w3.org/2000/svg" fill="#000000"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"><path d="M832 960H192s-64-2.133333-64-170.666667h768c0 168.533333-64 170.666667-64 170.666667z" fill="#42A5F5"></path><path d="M170.666667 64h682.666666v725.333333H170.666667z" fill="#90CAF9"></path><path d="M384 320h341.333333v42.666667H384zM384 405.333333h341.333333v42.666667H384zM384 490.666667h341.333333v42.666666H384zM384 576h341.333333v42.666667H384zM384 661.333333h341.333333v42.666667H384z" fill="#1976D2"></path><path d="M298.666667 320h42.666666v42.666667h-42.666666zM298.666667 405.333333h42.666666v42.666667h-42.666666zM298.666667 490.666667h42.666666v42.666666h-42.666666zM298.666667 576h42.666666v42.666667h-42.666666zM298.666667 661.333333h42.666666v42.666667h-42.666666z" fill="#1976D2"></path></g></svg>
                                </div>
                                <div class="pt-3 sm:pt-5">
                                    <h2 class="text-xl font-semibold text-black dark:text-white">SELECCIONE EL PROCEDIMIENTO A VISUALIZAR</h2>
                                </div>
                            </div>
                        </div>
                        <div class="grid gap-6 lg:grid-cols-1 lg:gap-8 animate__animated animate__zoomInDown animate__delay-0.8s">
                            <div class="grid gap-6 lg:grid-cols-1 lg:gap-8 animate__animated animate__zoomInDown animate__delay-0.8s">
                                <div class="flex flex-col gap-6 overflow-hidden rounded-lg bg-white p-6 shadow-[0px_14px_34px_0px_rgba(0,0,0,0.08)] ring-1 ring-white/[0.05] lg:p-10 lg:pb-10 dark:bg-zinc-900 dark:ring-zinc-800">
                                    
                                    @foreach($procedimientos as $proc)
                                    <a 
                                        href="{{ $proc['url'] }}" 
                                        target="_blank"
                                        class="group relative p-6 rounded-lg border border-gray-200 hover:border-blue-500 transition-all duration-300 shadow-sm hover:shadow-lg dark:border-zinc-700 dark:hover:border-blue-500"
                                    >
                                        <div class="flex justify-between items-center">
                                            <div class="flex-1 min-w-0">
                                                <h3 class="text-lg font-semibold text-gray-900 truncate dark:text-white">
                                                    {{ $proc['nombre'] }}
                                                </h3>
                                            </div>
                                            <div class="ml-4 flex items-center">
                                                <svg class="w-6 h-6 text-gray-400 group-hover:text-blue-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                                </svg>
                                            </div>
                                        </div>
                                        <div class="absolute inset-y-0 left-0 w-1 bg-blue-500 opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-r"></div>
                                    </a>
                                    @endforeach

                                </div>
                            </div>
                        </div>
</x-in-layout>