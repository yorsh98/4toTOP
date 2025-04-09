<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="animate-page-in">
    <head>
        
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>4toTOP</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,600&display=swap" rel="stylesheet" />

        <!-- Styles -->
        @stack('styles')
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <link rel="icon" href="{{ asset('favicon.png') }}" type="image/png">
        <link rel="stylesheet" href="{{ asset('css/index.css') }}">
        <link rel="stylesheet" href="{{ asset('css/nav2.css') }}">
        <link rel="stylesheet" href="{{ asset('css/btn-1.css') }}">
        <link href="https://cdn.datatables.net/2.2.2/css/dataTables.dataTables.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
 
    </head>
    <body class="antialiased font-sans ">
        <div class="relative min-h-screen ">
        <img id="background" src="{{ asset('img/blue.jpg') }}" alt="Fondo skyblue" class="absolute inset-0 w-full h-full object-cover "/>
            <div class="relative min-h-screen flex flex-col items-center justify-center selection:bg-[#FF2D20] selection:text-white">
                <div class="relative w-full max-w-2xl px-6 lg:max-w-7xl">
                <header class="fixed top-0 left-0 w-full z-50 bg-white shadow-md">
                    <div class="flex justify-center col-span-2 lg:col-span-1 lg:col-start-2"> 
                    <x-navbar />
                    </div>
                </header>
                    <main class="mt-6 pt-5">
                    
                        <div class="grid gap-6 pt-5 mb-6 lg:grid-cols-1 lg:gap-8 animate__animated animate__fadeInLeft animate__delay-0.5s">
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
                                <svg height="200px" width="200px" version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 512 512" xml:space="preserve" fill="#000000"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <g> <path style="fill:#2E36D2;" d="M38.788,170.667h23.273h23.273c12.853,0,23.273-10.42,23.273-23.273 c0-12.853-10.42-23.273-23.273-23.273H62.061H38.788c-12.853,0-23.273,10.42-23.273,23.273 C15.515,160.247,25.935,170.667,38.788,170.667z"></path> <path style="fill:#2E36D2;" d="M85.333,232.727H62.061H38.788c-12.853,0-23.273,10.42-23.273,23.273 c0,12.853,10.42,23.273,23.273,23.273h23.273h23.273c12.853,0,23.273-10.42,23.273-23.273 C108.606,243.147,98.186,232.727,85.333,232.727z"></path> <path style="fill:#2E36D2;" d="M85.333,341.333H62.061H38.788c-12.853,0-23.273,10.42-23.273,23.273s10.42,23.273,23.273,23.273 h23.273h23.273c12.853,0,23.273-10.42,23.273-23.273S98.186,341.333,85.333,341.333z"></path> </g> <g> <circle style="fill:#C9DDF4;" cx="279.273" cy="201.697" r="69.818"></circle> <path style="fill:#C9DDF4;" d="M279.273,271.514c-59.982,0-108.606,48.621-108.606,108.606h217.212 C387.879,320.135,339.254,271.514,279.273,271.514z"></path> </g> <path style="fill:#5286FA;" d="M473.212,0H279.273H162.909C107.301,0,62.061,45.241,62.061,100.848v23.273h23.273 c12.853,0,23.273,10.42,23.273,23.273c0,12.853-10.42,23.273-23.273,23.273H62.061v62.061h23.273 c12.853,0,23.273,10.42,23.273,23.273c0,12.853-10.42,23.273-23.273,23.273H62.061v62.061h23.273 c12.853,0,23.273,10.42,23.273,23.273s-10.42,23.273-23.273,23.273H62.061v23.273C62.061,466.759,107.301,512,162.909,512h116.364 h193.939c12.853,0,23.273-10.42,23.273-23.273V23.273C496.485,10.42,486.065,0,473.212,0z M279.273,380.121H170.667 c0-59.985,48.624-108.606,108.606-108.606c-38.558,0-69.818-31.26-69.818-69.818c0-38.561,31.26-69.818,69.818-69.818 c38.558,0,69.818,31.257,69.818,69.818c0,38.558-31.26,69.818-69.818,69.818c59.982,0,108.606,48.621,108.606,108.606H279.273z"></path> <path style="fill:#3D6DEB;" d="M170.667,380.12c0-59.985,48.624-108.606,108.606-108.606c-38.558,0-69.818-31.26-69.818-69.818 c0-38.56,31.26-69.818,69.818-69.818V0H162.909C107.301,0,62.061,45.241,62.061,100.848v23.273h23.273 c12.853,0,23.273,10.42,23.273,23.273c0,12.853-10.42,23.273-23.273,23.273H62.061v62.061h23.273 c12.853,0,23.273,10.42,23.273,23.273c0,12.853-10.42,23.273-23.273,23.273H62.061v62.061h23.273 c12.853,0,23.273,10.42,23.273,23.273s-10.42,23.273-23.273,23.273H62.061v23.273C62.061,466.759,107.301,512,162.909,512h116.364 V380.12H170.667z"></path> </g></svg>
                                </div>
                                <div class="pt-3 sm:pt-5">
                                    <h2 class="text-xl font-semibold text-black dark:text-white">SELECCIONE LA GUIA A VISUALIZAR</h2>
                                </div>
                            </div>
                        </div>
                        <div class="grid gap-6 lg:grid-cols-1 lg:gap-8 animate__animated animate__fadeInUp animate__delay-0.8s">
                            <div class="flex flex-col items-center gap-6 overflow-hidden rounded-lg bg-white p-6 shadow-[0px_14px_34px_0px_rgba(0,0,0,0.08)] ring-1 ring-white/[0.05] transition duration-300 focus:outline-none focus-visible:ring-[#FF2D20] md:row-span-3 lg:p-10 lg:pb-10 dark:bg-zinc-900 dark:ring-zinc-800 dark:hover:text-white/70 dark:hover:ring-zinc-700 dark:focus-visible:ring-[#FF2D20]">
                                <div class="flex flex-nowrap gap-[3px] overflow-x-auto  scroll-smooth">
                                    <button class="btn btn-1 filter-btn" data-institucion="0">TODOS</button>
                                    <button class="btn btn-1 filter-btn" data-institucion="1">4TO TOP</button>
                                    <button class="btn btn-1 filter-btn" data-institucion="2">GENCHI</button>
                                    <button class="btn btn-1 filter-btn" data-institucion="3">FISCALÍA</button>
                                    <button class="btn btn-1 filter-btn" data-institucion="4">CAPJ</button>
                                    <button class="btn btn-1 filter-btn" data-institucion="5">ZONAL STGO</button>
                                    <button class="btn btn-1 filter-btn" data-institucion="12">OTROS TRIBUNALES</button>
                                </div>    
                                <div class="flex flex-nowrap gap-[3px] overflow-x-auto  scroll-smooth">
                                    <button class="btn btn-1 filter-btn" data-institucion="6">DEFENSORIA</button> 
                                    <button class="btn btn-1 filter-btn" data-institucion="7">MIN. INTERIOR</button> 
                                    <button class="btn btn-1 filter-btn" data-institucion="8">C.D. ESTADO</button> 
                                    <button class="btn btn-1 filter-btn" data-institucion="9">DEF. PRIVADOS</button>
                                    <button class="btn btn-1 filter-btn" data-institucion="10">PDI/CARABINEROS</button>
                                    <button class="btn btn-1 filter-btn" data-institucion="11">SML</button>
                                </div> 

                            <!-- Tabla -->
                                <table id="guias-table" class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Nombre Completo</th>
                                            <th>RUT</th>
                                            <th>Email</th>
                                            <th>Teléfono 1</th>
                                            <th>Teléfono 2</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </main>


                    <footer class="py-16 text-center text-sm text-black dark:text-white/70">
                        
                        Pagina web desarrollada por Jorge Troncoso
                    </footer>
                </div>
            </div>
        </div>
        <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
        <script src="https://cdn.datatables.net/2.2.2/js/dataTables.min.js"></script>
        
        <script>
           $(document).ready(function() {
            let table = $('#guias-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('guias.data') }}",
                    data: function(d) {
                        d.institucion = $('.filter-btn.active').attr('data-institucion') || '';
                    }
                },
                createdRow: function(row, data, dataIndex) {
                    // Animación para cada fila de la tabla
                    $(row).addClass('animate__animated animate__fadeIn')
                          .css('animation-delay', `${dataIndex * 0.08}s`);
                },
                columns: [
                    { data: 'nombre_completo', name: 'nombre_completo' },
                    { data: 'rut', name: 'rut' },
                    { data: 'email', name: 'email' },
                    { data: 'telefono1', name: 'telefono1' },
                    { data: 'telefono2', name: 'telefono2' }
                ],
                language: {
                    "sProcessing": "Procesando...",
                    "sZeroRecords": "No se encontraron resultados",
                    "sLengthMenu":     "",
                    "sEmptyTable": "No hay datos disponibles en la tabla",
                    "sInfo": "Mostrando _START_ a _END_ de _TOTAL_ registros",
                    "sInfoEmpty": "Mostrando 0 a 0 de 0 registros",
                    "sInfoFiltered": "(filtrado de _MAX_ registros en total)",
                    "sSearch": "Buscar:",
                    "oPaginate": {
                        "sFirst": "Primera",
                        "sPrevious": "Anterior",
                        "sNext": "Siguiente",
                        "sLast": "Última"
                    }
                }
            });
            
            // Animación al hacer click en los filtros
            $('.filter-btn').on('click', function() {
                $('.filter-btn').removeClass('active btn-secondary').addClass('btn-primary');
                $(this).addClass('active btn-secondary animate__animated animate__pulse')
                       .removeClass('btn-primary');
                table.ajax.reload();
            });
        });
    </script>
    
    </body>
</html>

