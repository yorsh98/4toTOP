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
    </head>
    <body class="bg-gray-100 flex flex-col min-h-screen">
        <x-headerSist />
            <main class="flex flex-1 gap-2 p-2 pb-6">
                <!-- Panel izquierdo-->
                <div class="w-1/3 bg-white rounded-lg shadow-lg overflow-hidden border border-gray-200">
                    <div class="h-full overflow-y-auto p-6">
                        <h3 class="text-2xl font-bold text-center mb-6 text-gray-800">ATENCION DE PUBLICO</h3>
                        <!-- Contenido dinámico -->
                        <!--Livewire -->
                    </div>
                </div>
                
                <!-- Panel derecho-->
                <div class="w-2/3 bg-white rounded-lg shadow-lg overflow-hidden border border-gray-200">
                    <div class="h-full overflow-y-auto p-6">
                        <h3 class="text-2xl font-bold text-center mb-6 text-gray-800">AUDIENCIAS CORTAS</h3>
                        <!-- Contenido dinámico -->
                        <!--Livewire -->
                    </div>
                </div>
            </main>
        <footer class="footer-Monitor">
            <a href="" class="footer-button-Monitor">
                PROGRAMACIÓN DE AUDIENCIAS - {{ strtoupper(\Carbon\Carbon::now()->isoFormat('dddd DD-MM-YYYY')) }}
            </a> 
        </footer>
    </body>
</html>




