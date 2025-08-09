<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>4toTOP</title>

        <!-- Fonts -->
        
        

        <!-- Styles -->
        @stack('styles')
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <link rel="icon" href="{{ asset('favicon.webp') }}" type="image/webp">
        <link rel="stylesheet" href="{{ asset('css/index.css') }}">
        <link rel="stylesheet" href="{{ asset('css/nav2.css') }}">
        <link rel="stylesheet" href="{{ asset('css/btn-1.css') }}">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">

        
 
    </head>
    <body class="antialiased font-sans ">
        <div class="relative min-h-screen ">
        <img id="background" src="{{ asset('img/blue.webp') }}" alt="Fondo skyblue" class="absolute inset-0 w-full h-full object-cover "/>
            <div class="relative min-h-screen flex flex-col items-center justify-center selection:bg-[#FF2D20] selection:text-white">
                <div class="relative w-full max-w-2xl px-6 lg:max-w-7xl">
                <header class="fixed top-0 left-0 w-full z-50 bg-white shadow-md">
                    <div class="flex justify-center col-span-2 lg:col-span-1 lg:col-start-2"> 
                    <x-navbar2 />
                    </div>
                </header>
                    <main class="mt-6 pt-5">
                    {{ $slot }}
                    </main>


                    <footer class="py-16 text-center text-sm text-black dark:text-white/70">
                        
                        Pagina web desarrollada por Jorge Troncoso
                    </footer>
                </div>
            </div>
        </div>
        
    
    </body>
    @stack('scripts')    
</html>

