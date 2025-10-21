<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema OFICIO/LIBERTADES </title>
    @stack('styles')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <!-- oculto por generar nuevo layout para sistema reemplazado por vite
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Roboto|Varela+Round">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css"> -->
    <link rel="stylesheet" href="/css/app.css">
    <link rel="icon" href="{{ asset('favicon.webp') }}" type="image/webp">
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
    <body>
        <x-headerSist />            
            <main class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8 py-4 sm:py-6">
                <div class="bg-sky-50 shadow-sm sm:rounded-lg overflow-hidden">
                    {{-- Encapsula contenido ancho (tablas, pre, code) sin romper el layout --}}
                    <div class="p-4 sm:p-6 lg:p-8 text-gray-900 overflow-x-auto">
                        {{ $slot }}
                    </div>
                </div>
            </main>
        <x-footer-sol />       
    @stack('scripts') 
    </body>
</html>




