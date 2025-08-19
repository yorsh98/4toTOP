<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Monitor de audiencias</title>
        @vite(['resources/css/app.css', 'resources/js/app.js']) 
        <link rel="stylesheet" href="/css/app.css">
        <link rel="icon" href="{{ asset('favicon.webp') }}" type="image/webp">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        @livewireStyles
        @stack('styles')
    </head>
    <body class="bg-gray-100 flex flex-col min-h-screen">
        <x-headerSist />
            <main class="flex flex-1 gap-2 p-2 pb-6">
                <!-- Panel izquierdo-->
                <div class="w-1/3 bg-white rounded-lg shadow-lg overflow-hidden border border-gray-200">
                    <div class="h-full p-4 flex flex-col">
                        <div class="text-center mb-4 bg-white rounded-xl shadow-md p-4 relative overflow-hidden">
                            <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-blue-500 via-green-500 to-purple-600"></div>
                            <h1 class="text-2xl font-bold text-gray-800 mb-2 flex items-center justify-center gap-3">
                                <i class="fas fa-hands-helping"></i>
                                ATENCION DE PUBLICO
                                <i class="fas fa-comments"></i> 
                            </h1>            
                        </div>
                        
                        <!-- Contenedor para Livewire -->
                        <div class="flex">
                           
                        </div>
                    </div>
                </div>
                
                <!-- Panel derecho-->
                <!-- Panel derecho ajustado -->
                <div class="w-2/3 bg-white rounded-lg shadow-lg overflow-hidden border border-gray-200">
                    <div class="h-full p-4 flex flex-col">
                        <div class="text-center mb-4 bg-white rounded-xl shadow-md p-4 relative overflow-hidden">
                            <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-blue-500 via-green-500 to-purple-600"></div>
                            <h1 class="text-2xl font-bold text-gray-800 mb-2 flex items-center justify-center gap-3">
                                <i class="fas fa-balance-scale"></i>
                                PROGRAMACION DE AUDIENCIAS
                                <i class="far fa-calendar-alt"></i> 
                            </h1>            
                        </div>
                        
                        <!-- Contenedor para Livewire -->
                        <div class="flex">
                            <livewire:monitor-audiencias/>
                        </div>
                    </div>
                </div>
            </main>
        <footer class="footer-Monitor">
            <a href="" class="footer-button-Monitor">
                PROGRAMACIÓN DE AUDIENCIAS - {{ strtoupper(\Carbon\Carbon::now()->isoFormat('dddd DD-MM-YYYY')) }}
            </a> 
        </footer>
        @stack('scripts')
        @livewireScripts
        <script>
             // Ping al servidor cada 5 minutos para mantener la sesión activa
            setInterval(() => {
                fetch('/csrf-refresh')
                    .then(res => res.json())
                    .then(data => {
                        if (data.csrf) {
                            document.querySelector('meta[name="csrf-token"]').setAttribute("content", data.csrf);
                        }
                    });
            }, 10 * 60 * 500); // 5 minutos
           
        </script>
    </body>
</html>




