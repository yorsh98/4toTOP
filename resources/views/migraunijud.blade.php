<x-in-layout>
    @php
        $tribunales = [
            [                
                'nombre' => '10° Juzgado de Garantía de Santiago',                
            ],
            [                
                'nombre' => '11° Juzgado de Garantía de Santiago',                
            ],
            [                
                'nombre' => '12° Juzgado de Garantía de Santiago',                
            ],
            [                
                'nombre' => '15° Juzgado de Garantía de Santiago',                
            ],
            [                
                'nombre' => 'Juzgado de Garantía de Illapel',                
            ],
            [                
                'nombre' => 'Juzgado de Garantía de los Lagos',
            ],
            [                
                'nombre' => 'Juzgado de Garantía de Valdivia',
            ],
            [                
                'nombre' => 'Juzgado de Garantía de Osorno',
            ],
            [                
                'nombre' => 'Juzgado de Garantía de Mariquina',
            ],
            [                
                'nombre' => 'Juzgado de Garantía de Río Negro',
            ],
            [                
                'nombre' => 'Juzgado de Garantía de Ovalle',
            ],
            [                
                'nombre' => 'Juzgado de Garantía de Vicuña',
            ],
            [                
                'nombre' => 'Juzgado de Garantía de Coquimbo',
            ],
            [                
                'nombre' => 'Juzgado de Garantía de Antofagasta',
            ],
            [                
                'nombre' => 'Juzgado de Garantía de La Serena',
            ],
            [                
                'nombre' => 'Juzgado de Garantía de Calama',
            ],
            [                
                'nombre' => 'Juzgado de Garantía de Tocopilla',
            ],
            [                
                'nombre' => 'Juzgado de Garantía de Punta Arenas',
            ],
            [                
                'nombre' => 'Juzgado de Garantía de Diego de Almagro',
            ],
            /*[                
                'nombre' => 'Juzgado de Garantía de ',
            ],*/
            [                
                'nombre' => 'Juzgado de Letras y Garantía de Panguipulli',                
            ],
            [                
                'nombre' => 'Juzgado de Letras y Garantía de Andacollo',                
            ],
            [                
                'nombre' => 'Juzgado de Letras y Garantía de Río Bueno',                
            ],
            [                
                'nombre' => 'Juzgado de Letras y Garantía de La Unión',                
            ],
            [                
                'nombre' => 'Juzgado de Letras y Garantía de Paillaco',                
            ],
            [                
                'nombre' => 'Juzgado de Letras y Garantía de María Elena',                
            ],
            [                
                'nombre' => 'Juzgado de Letras y Garantía de Mejillones ',                
            ],
            [                
                'nombre' => 'Juzgado de Letras y Garantía de Cabo de Hornos',                
            ],
            [                
                'nombre' => 'Juzgado de Letras y Garantía de Porvenir',                
            ],
            [                
                'nombre' => 'Juzgado de Letras y Garantía de Chile Chico',                
            ],
            /*[                
                'nombre' => '',                
            ],
            [                
                'nombre' => '',                
            ],
            [                
                'nombre' => '',                
            ],*/

            //si necesita agregar otro tribunal copiar desde y hasta [] para poder generar un nuevo modulo en la vista
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
                                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path fill-rule="evenodd" clip-rule="evenodd" d="M14.9703 3.3437C13.0166 2.88543 10.9834 2.88543 9.02975 3.3437C6.20842 4.00549 4.0055 6.20841 3.3437 9.02975C2.88543 10.9834 2.88543 13.0166 3.3437 14.9703C4.0055 17.7916 6.20842 19.9945 9.02975 20.6563C10.9834 21.1146 13.0166 21.1146 14.9703 20.6563C17.7916 19.9945 19.9945 17.7916 20.6563 14.9703C21.1146 13.0166 21.1146 10.9834 20.6563 9.02975C19.9945 6.20842 17.7916 4.00549 14.9703 3.3437ZM10.9883 8.22523C11.2226 8.45955 11.2226 8.83945 10.9883 9.07376L9.07376 10.9883C8.96124 11.1009 8.80863 11.1641 8.6495 11.1641C8.49037 11.1641 8.33775 11.1009 8.22523 10.9883L7.26795 10.031C7.03363 9.79673 7.03363 9.41683 7.26795 9.18252C7.50226 8.9482 7.88216 8.9482 8.11647 9.18252L8.6495 9.71554L10.1398 8.22523C10.3741 7.99092 10.754 7.99092 10.9883 8.22523ZM12.3573 10.0854C12.3573 9.75406 12.6259 9.48543 12.9573 9.48543H16.3078C16.6392 9.48543 16.9078 9.75406 16.9078 10.0854C16.9078 10.4168 16.6392 10.6854 16.3078 10.6854H12.9573C12.6259 10.6854 12.3573 10.4168 12.3573 10.0854ZM10.9883 13.0117C11.2226 13.246 11.2226 13.6259 10.9883 13.8602L9.07376 15.7748C8.83945 16.0091 8.45955 16.0091 8.22523 15.7748L7.26795 14.8175C7.03363 14.5832 7.03363 14.2033 7.26795 13.969C7.50226 13.7346 7.88216 13.7346 8.11647 13.969L8.6495 14.502L10.1398 13.0117C10.3741 12.7774 10.754 12.7774 10.9883 13.0117ZM12.3573 14.8719C12.3573 14.5405 12.6259 14.2719 12.9573 14.2719H16.3078C16.6392 14.2719 16.9078 14.5405 16.9078 14.8719C16.9078 15.2032 16.6392 15.4719 16.3078 15.4719H12.9573C12.6259 15.4719 12.3573 15.2032 12.3573 14.8719Z" fill="#363853"></path> </g></svg>
                                </div>
                                <div class="pt-3 sm:pt-5">
                                    <h2 class="text-xl font-semibold text-black dark:text-white">LISTADO DE TRIBUNALES MIGRADOS A UNIJUD</h2>
                                </div>                                
                            </div>
                            <span class="bg-white rounded-lg pt-2 pb-2">Fecha de actualizacion 16-02-2026</span>
                        </div>
                        <div class="grid gap-6 lg:grid-cols-1 lg:gap-8 animate__animated animate__zoomInDown animate__delay-0.8s">
                            <div class="grid gap-6 lg:grid-cols-1 lg:gap-8 animate__animated animate__zoomInDown animate__delay-0.8s">
                                <div class="flex flex-col gap-6 overflow-hidden rounded-lg bg-white p-6 shadow-[0px_14px_34px_0px_rgba(0,0,0,0.08)] ring-1 ring-white/[0.05] lg:p-10 lg:pb-10 dark:bg-zinc-900 dark:ring-zinc-800">
                                    
                                    @foreach($tribunales as $trib)
                                    <div>
                                        <div class="flex justify-between items-center">
                                            <div class="flex-1 min-w-0">
                                                <h3 class="text-lg font-semibold text-gray-900 truncate dark:text-white">
                                                    {{ $trib['nombre'] }}
                                                </h3>
                                            </div>
                                            <div class="ml-4 flex items-center">
                                                <svg class="w-6 h-6 text-gray-400 group-hover:text-blue-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                                </svg>
                                            </div>
                                        </div>
                                        <div class="absolute inset-y-0 left-0 w-1 bg-blue-500 opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-r"></div>
                                    </div><hr>
                                    @endforeach

                                </div>
                            </div>
                        </div>
</x-in-layout>