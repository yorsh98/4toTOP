<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', '4toTOP') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        <link rel="icon" href="{{ asset('favicon.png') }}" type="image/png">
        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="relative min-h-screen">
            <img id="background" src="{{ asset('img/blue.webp') }}" alt="Fondo skyblue" class="absolute inset-0 h-full w-full object-cover" />

            <div class="relative min-h-screen flex flex-col items-center justify-center px-4 py-6 selection:bg-[#FF2D20] selection:text-white">
                <div>
                    <a href="/" wire:navigate>
                        <x-application-logo class="h-20 w-20 fill-current text-gray-500" alt="Logo" style="width: 416px; height: 216px;" />
                    </a>
                </div>

                <div class="mt-6 w-full sm:max-w-md rounded-xl bg-white/92 px-6 py-4 shadow-[0_12px_30px_rgba(15,23,42,0.14)] ring-1 ring-slate-900/10 backdrop-blur-[2px]">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </body>
</html>
