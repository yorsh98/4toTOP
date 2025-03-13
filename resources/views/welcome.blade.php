<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
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
        <link rel="stylesheet" href="{{ asset('css/nav.css') }}">
        
        
    </head>
    <body class="antialiased font-sans">
        <div class="relative min-h-screen"">
        <img id="background" src="{{ asset('img/blue.jpg') }}" alt="Fondo skyblue" class="absolute inset-0 w-full h-full object-cover "/>
            <div class="relative min-h-screen flex flex-col items-center justify-center selection:bg-[#FF2D20] selection:text-white">
                <div class="relative w-full max-w-2xl px-6 lg:max-w-7xl">
                <header class="grid grid-cols-1 items-center gap-2 py- lg:grid-cols-3">
                    <div class="flex justify-center col-span-2 lg:col-span-1 lg:col-start-2"> <!-- Contenedor con ancho máximo -->
                        <x-application-logo class="block h-7 w-auto fill-current text-gray-800 lg:h-7" /> <!-- Redujimos a h-6 -->
                    </div>
                </header>
                <x-navbar />


                    <main class="mt-6">
                    <div class="grid gap-6 pt-1 mb-6 lg:grid-cols-1 lg:gap-8">
                            <a
                                href="#"
                                class="flex justify-center items-start gap-4 rounded-lg bg-white p-6 shadow-[0px_14px_34px_0px_rgba(0,0,0,0.08)] ring-1 ring-white/[0.05] transition duration-300 hover:text-black/70 hover:ring-black/20 focus:outline-none focus-visible:ring-[#FF2D20] lg:pb-10 dark:bg-zinc-900 dark:ring-zinc-800 dark:hover:text-white/70 dark:hover:ring-zinc-700 dark:focus-visible:ring-[#FF2D20]"
                            >
                                <div class="flex size-12 shrink-0 items-center justify-center rounded-full sm:size-16">
                                <svg height="200px" width="200px" version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 512 512" xml:space="preserve" fill="#000000"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <g> <path style="fill:#D55400;" d="M433.253,125.646h-68.299c-10.777,0-19.514-8.737-19.514-19.514V82.324 c0-29.59,24.074-53.664,53.664-53.664s53.664,24.074,53.664,53.664v23.807C452.767,116.909,444.031,125.646,433.253,125.646z"></path> <path style="fill:#D55400;" d="M433.253,483.34h-68.299c-10.777,0-19.514-8.737-19.514-19.514v-120.61 c0-10.777,8.737-19.514,19.514-19.514h68.299c10.777,0,19.514,8.737,19.514,19.514v120.61 C452.767,474.604,444.031,483.34,433.253,483.34z"></path> </g> <g> <path style="fill:#E87E04;" d="M147.045,125.646H78.746c-10.777,0-19.514-8.737-19.514-19.514V82.324 c0-29.59,24.074-53.664,53.664-53.664s53.664,24.074,53.664,53.664v23.807C166.56,116.909,157.822,125.646,147.045,125.646z"></path> <path style="fill:#E87E04;" d="M147.045,483.34H78.746c-10.777,0-19.514-8.737-19.514-19.514v-120.61 c0-10.777,8.737-19.514,19.514-19.514h68.299c10.777,0,19.514,8.737,19.514,19.514v120.61 C166.56,474.604,157.822,483.34,147.045,483.34z"></path> </g> <path style="fill:#F2C500;" d="M466.467,362.729H45.533C20.426,362.729,0,342.303,0,317.196V132.15 c0-25.107,20.426-45.533,45.533-45.533h420.934c25.107,0,45.533,20.426,45.533,45.533v185.046 C512,342.303,491.573,362.729,466.467,362.729z"></path> <path style="fill:#F59D00;" d="M466.467,86.617H255.999v276.112h210.466c25.107,0,45.533-20.426,45.533-45.533V132.15 C512,107.043,491.573,86.617,466.467,86.617z"></path> <g> <path style="fill:#808285;" d="M116.532,246.625c-2.036,5.935-15.559,5.491-16.815,0l-4.285-19.534l-4.287,19.534 c-1.274,5.569-14.975,5.854-16.813,0L57.93,196.349c-1.698-5.081,10.836-8.842,12.281-3.791l12.528,42.282l6.511-27.858 c0.998-4.493,11.366-4.49,12.363,0l6.511,27.858l12.528-42.282c1.439-5.04,13.96-1.31,12.281,3.791L116.532,246.625z"></path> <path style="fill:#808285;" d="M157.254,215.305h12.363c5.083,0,5.24,9.891,0,9.891h-12.363v14.011h22.089 c5.096,0,5.255,11.209,0,11.209h-29.341c-2.802,0-5.606-1.319-5.606-3.956v-52.337c0-2.637,2.802-3.956,5.606-3.956h29.341 c5.258,0,5.093,11.209,0,11.209h-22.089V215.305z"></path> <path style="fill:#808285;" d="M201.022,250.416c-2.802,0-5.606-1.319-5.606-3.956v-52.42c0-2.72,3.215-3.873,6.428-3.873 c3.216,0,6.429,1.154,6.429,3.873v45.166h18.791c4.946,0,4.946,11.209,0,11.209h-26.042V250.416z"></path> <path style="fill:#808285;" d="M261.275,201.376c-4.698,0-7.418,2.637-7.418,8.243v21.842c0,5.604,2.72,8.241,7.5,8.241 c6.593,0,7.007-5.028,7.253-8.241c0.247-3.049,3.051-3.874,6.347-3.874c4.451,0,6.511,1.154,6.511,6.1 c0,21.775-40.468,25.498-40.468-2.226v-21.842c0-27.783,40.468-23.656,40.468-3.049c0,4.945-2.061,6.099-6.428,6.099 c-3.462,0-6.265-0.907-6.429-3.874C268.526,206.651,268.28,201.376,261.275,201.376z"></path> <path style="fill:#808285;" d="M293.917,231.46v-21.842c0-14.176,8.819-19.452,20.195-19.452c11.373,0,20.275,5.275,20.275,19.452 v21.842c0,14.176-8.902,19.452-20.275,19.452C302.736,250.911,293.917,245.637,293.917,231.46z M321.526,209.619 c0-5.688-2.801-8.243-7.417-8.243s-7.336,2.555-7.336,8.243v21.842c0,5.686,2.72,8.241,7.336,8.241s7.417-2.555,7.417-8.241 V209.619z"></path> <path style="fill:#808285;" d="M378.236,229.071c-1.841,3.116-6.593,3.32-8.323,0l-7.829-14.342v31.733 c0,5.275-12.859,5.275-12.859,0v-49.865c0-4.698,3.215-6.428,6.429-6.428c4.614,0,6.593,0.494,9.972,6.428l8.49,14.836 l8.489-14.836c3.38-5.935,5.356-6.428,9.973-6.428c3.295,0,6.428,1.73,6.428,6.428v49.865c0,5.275-12.859,5.275-12.859,0v-30.908 L378.236,229.071z"></path> <path style="fill:#808285;" d="M427.363,215.305h12.363c5.083,0,5.24,9.891,0,9.891h-12.363v14.011h22.089 c5.096,0,5.254,11.209,0,11.209h-29.341c-2.802,0-5.606-1.319-5.606-3.956v-52.337c0-2.637,2.802-3.956,5.606-3.956h29.341 c5.256,0,5.094,11.209,0,11.209h-22.089V215.305z"></path> </g> <g> <path style="fill:#6D6E71;" d="M274.954,227.589c-3.291,0-6.101,0.82-6.349,3.877c-0.247,3.213-0.65,8.235-7.246,8.235 c-2.277,0-4.085-0.598-5.36-1.847v12.814c11.526,2.368,25.472-4.189,25.472-16.977 C281.472,228.745,279.403,227.589,274.954,227.589z M255.999,190.433v12.762c1.249-1.224,3.031-1.821,5.269-1.821 c7.012,0,7.259,5.282,7.337,7.415c0.169,2.966,2.966,3.877,6.427,3.877c4.371,0,6.44-1.158,6.44-6.101 C281.472,194.465,267.513,188.052,255.999,190.433z"></path> <path style="fill:#6D6E71;" d="M293.917,231.46v-21.842c0-14.176,8.819-19.452,20.195-19.452c11.373,0,20.275,5.275,20.275,19.452 v21.842c0,14.176-8.902,19.452-20.275,19.452C302.736,250.911,293.917,245.637,293.917,231.46z M321.526,209.619 c0-5.688-2.801-8.243-7.417-8.243s-7.336,2.555-7.336,8.243v21.842c0,5.686,2.72,8.241,7.336,8.241s7.417-2.555,7.417-8.241 V209.619z"></path> <path style="fill:#6D6E71;" d="M378.236,229.071c-1.841,3.116-6.593,3.32-8.323,0l-7.829-14.342v31.733 c0,5.275-12.859,5.275-12.859,0v-49.865c0-4.698,3.215-6.428,6.429-6.428c4.614,0,6.593,0.494,9.972,6.428l8.49,14.836 l8.489-14.836c3.38-5.935,5.356-6.428,9.973-6.428c3.295,0,6.428,1.73,6.428,6.428v49.865c0,5.275-12.859,5.275-12.859,0v-30.908 L378.236,229.071z"></path> <path style="fill:#6D6E71;" d="M427.363,215.305h12.363c5.083,0,5.24,9.891,0,9.891h-12.363v14.011h22.089 c5.096,0,5.254,11.209,0,11.209h-29.341c-2.802,0-5.606-1.319-5.606-3.956v-52.337c0-2.637,2.802-3.956,5.606-3.956h29.341 c5.256,0,5.094,11.209,0,11.209h-22.089V215.305z"></path> </g> </g></svg>
                                </div>

                                <div class="pt-3 sm:pt-5">
                                    <h2 class="text-xl font-semibold text-black dark:text-white">BIENVENIDO A LA INTRANET DEL 4TO TRIBUNAL DE JUICIO ORAL EN LO PENAL DE SANTIAGO</h2>

                                    
                                </div>

                                
                            </a>
                        </div>
                        <div id="1" class="grid gap-6 lg:grid-cols-2 lg:gap-8">
                            <div  
                                
                                class="flex flex-col items-start gap-6 overflow-hidden rounded-lg bg-white p-6 shadow-[0px_14px_34px_0px_rgba(0,0,0,0.08)] ring-1 ring-white/[0.05] transition duration-300 hover:text-black/70 hover:ring-black/20 focus:outline-none focus-visible:ring-[#FF2D20] md:row-span-3 lg:p-10 lg:pb-10 dark:bg-zinc-900 dark:ring-zinc-800 dark:hover:text-white/70 dark:hover:ring-zinc-700 dark:focus-visible:ring-[#FF2D20]"
                            >
                                <div id="screenshot-container" class="relative flex w-full flex-1 items-stretch">
                                <section>
                                    <img src="{{ asset('img/S1.jpg') }}" onclick="window.location.href='{{ url('/SistOficioLibertades') }}'">
                                    <img src="{{ asset('img/S2.jpg') }}" onclick="window.location.href='http://10.13.214.129:8080/webaudios/'">
                                    <img src="{{ asset('img/S3.jpg') }}" onclick="window.location.href='http://10.13.214.129:8080/cumpleanos/'">
                                </section>
                                    <div
                                        class="absolute -bottom-16 -left-16 h-40 w-[calc(100%+8rem)] bg-gradient-to-b from-transparent via-white to-white dark:via-zinc-900 dark:to-zinc-900"
                                    ></div>
                                </div>

                                <div class="relative flex items-center gap-6 lg:items-end">
                                <div class="flex size-12 shrink-0 items-center justify-center rounded-full bg-[#FF2D20]/10 sm:size-16">
                                <svg fill="#ff483d" viewBox="-9.6 -9.6 115.20 115.20" xmlns="http://www.w3.org/2000/svg" stroke="#ff483d"><g id="SVGRepo_bgCarrier" stroke-width="0" transform="translate(0,0), scale(1)"><rect x="-9.6" y="-9.6" width="115.20" height="115.20" rx="57.6" fill="#ffebe9" strokewidth="0"></rect></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round" stroke="#CCCCCC" stroke-width="0.384"></g><g id="SVGRepo_iconCarrier"> <path d="M38.251 9.482c-40.067 11.274-39.759 66.555.429 77.023 3.157.822 7.446 1.495 9.53 1.495H52V77.2c0-11.691-.582-13.2-5.095-13.2-2.414 0-2.735-.417-3.076-4l-.382-4H52v-8H36v-4c0-3.376.322-4 2.066-4C42.505 40 44 38.228 44 32.965V28h4.566c3.772 0 7.455-1.017 9.276-2.561.188-.16.896-1.9 1.573-3.869l1.23-3.578 4.73 3.133C73.436 26.464 80 37.398 80 45.485c0 3.16 6.71 9.433 7.603 7.108 1.947-5.076-2.032-20.27-7.305-27.892C71.647 12.199 52.817 5.383 38.251 9.482m-1.382 8.423c-5.683 2.223-10.863 6.088-14.432 10.767l-2.698 3.537 4.13 4.131L28 40.471v-3.036C28 33.655 29.583 32 33.2 32c2.645 0 2.8-.245 2.8-4.429C36 21.339 37.693 20 45.571 20 51.333 20 52 19.793 52 18c0-2.661-8.436-2.714-15.131-.095m-20.46 24.47c-1.154 3.01.235 14.032 2.381 18.883 3.638 8.224 13.362 16.084 22.46 18.154 4.286.975 4.177-6.578-.12-8.358C37.959 69.74 36 66.353 36 62.183c0-3.78-18.546-22.531-19.591-19.808M60 60.027v19.214l4.555-3.732c2.505-2.053 4.704-3.57 4.885-3.371.182.199 2.013 4.253 4.069 9.01l3.737 8.647 3.627-1.505c1.995-.828 3.939-1.693 4.32-1.922.381-.228-1.081-4.275-3.25-8.991C79.774 72.66 78 68.621 78 68.4c0-.22 2.354-.4 5.23-.4h5.23l-9.48-9.572c-5.214-5.265-11.618-11.382-14.23-13.593L60 40.814v19.213" fill-rule="evenodd"></path> </g></svg>
                                </div>

                                <div class="!pt-0">
                                    <h2 class="text-xl font-semibold text-black dark:text-white">SISTEMAS INTERNOS</h2>
                                    <p class="mt-4 text-sm/relaxed">
                                        PRECIONE SOBRE EL SISTEMA A UTILIZAR 👆🏻
                                    </p>
                                    
                                </div>

                                <svg class="size-6 shrink-0 self-center stroke-[#FF2D20]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12h15m0 0l-6.75-6.75M19.5 12l-6.75 6.75"/></svg>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-4">
                                <a  id="2"
                                    href="https://cloud.pjud.cl/index.php/s/FG9miZGQsAAyFjN" 
                                    class="flex items-center gap-4 rounded-lg bg-white p-6 shadow-[0px_14px_34px_0px_rgba(0,0,0,0.08)] ring-1 ring-white/[0.05] transition duration-300 hover:text-black/70 hover:ring-black/20 focus:outline-none focus-visible:ring-[#FF2D20] lg:pb-10 dark:bg-zinc-900 dark:ring-zinc-800 dark:hover:text-white/70 dark:hover:ring-zinc-700 dark:focus-visible:ring-[#FF2D20]">
                                    <div class="size-12 shrink-0 flex items-center justify-center rounded-full bg-red-200">
                                        <svg height="200px" width="200px" version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 512 512" xml:space="preserve" fill="#000000"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <g> <path style="fill:#2E36D2;" d="M38.788,170.667h23.273h23.273c12.853,0,23.273-10.42,23.273-23.273 c0-12.853-10.42-23.273-23.273-23.273H62.061H38.788c-12.853,0-23.273,10.42-23.273,23.273 C15.515,160.247,25.935,170.667,38.788,170.667z"></path> <path style="fill:#2E36D2;" d="M85.333,232.727H62.061H38.788c-12.853,0-23.273,10.42-23.273,23.273 c0,12.853,10.42,23.273,23.273,23.273h23.273h23.273c12.853,0,23.273-10.42,23.273-23.273 C108.606,243.147,98.186,232.727,85.333,232.727z"></path> <path style="fill:#2E36D2;" d="M85.333,341.333H62.061H38.788c-12.853,0-23.273,10.42-23.273,23.273s10.42,23.273,23.273,23.273 h23.273h23.273c12.853,0,23.273-10.42,23.273-23.273S98.186,341.333,85.333,341.333z"></path> </g> <g> <circle style="fill:#C9DDF4;" cx="279.273" cy="201.697" r="69.818"></circle> <path style="fill:#C9DDF4;" d="M279.273,271.514c-59.982,0-108.606,48.621-108.606,108.606h217.212 C387.879,320.135,339.254,271.514,279.273,271.514z"></path> </g> <path style="fill:#5286FA;" d="M473.212,0H279.273H162.909C107.301,0,62.061,45.241,62.061,100.848v23.273h23.273 c12.853,0,23.273,10.42,23.273,23.273c0,12.853-10.42,23.273-23.273,23.273H62.061v62.061h23.273 c12.853,0,23.273,10.42,23.273,23.273c0,12.853-10.42,23.273-23.273,23.273H62.061v62.061h23.273 c12.853,0,23.273,10.42,23.273,23.273s-10.42,23.273-23.273,23.273H62.061v23.273C62.061,466.759,107.301,512,162.909,512h116.364 h193.939c12.853,0,23.273-10.42,23.273-23.273V23.273C496.485,10.42,486.065,0,473.212,0z M279.273,380.121H170.667 c0-59.985,48.624-108.606,108.606-108.606c-38.558,0-69.818-31.26-69.818-69.818c0-38.561,31.26-69.818,69.818-69.818 c38.558,0,69.818,31.257,69.818,69.818c0,38.558-31.26,69.818-69.818,69.818c59.982,0,108.606,48.621,108.606,108.606H279.273z"></path> <path style="fill:#3D6DEB;" d="M170.667,380.12c0-59.985,48.624-108.606,108.606-108.606c-38.558,0-69.818-31.26-69.818-69.818 c0-38.56,31.26-69.818,69.818-69.818V0H162.909C107.301,0,62.061,45.241,62.061,100.848v23.273h23.273 c12.853,0,23.273,10.42,23.273,23.273c0,12.853-10.42,23.273-23.273,23.273H62.061v62.061h23.273 c12.853,0,23.273,10.42,23.273,23.273c0,12.853-10.42,23.273-23.273,23.273H62.061v62.061h23.273 c12.853,0,23.273,10.42,23.273,23.273s-10.42,23.273-23.273,23.273H62.061v23.273C62.061,466.759,107.301,512,162.909,512h116.364 V380.12H170.667z"></path> </g></svg>
                                    </div>
                                    <div class="">
                                        <h2 class="text-xl font-semibold text-black">GUIAS TELEFONICA</h2>
                                        
                                    </div>
                                </a>

                                <a  id="3"
                                    href="https://cloud.pjud.cl/index.php/s/2X93Tx4MMAw4wEc" 
                                    class="flex items-center gap-4 rounded-lg bg-white p-6 shadow-[0px_14px_34px_0px_rgba(0,0,0,0.08)] ring-1 ring-white/[0.05] transition duration-300 hover:text-black/70 hover:ring-black/20 focus:outline-none focus-visible:ring-[#FF2D20] lg:pb-10 dark:bg-zinc-900 dark:ring-zinc-800 dark:hover:text-white/70 dark:hover:ring-zinc-700 dark:focus-visible:ring-[#FF2D20]">
                                    <div class="size-12 shrink-0 flex items-center justify-center rounded-full bg-blue-200">
                                        <svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 512 512" xml:space="preserve" fill="#000000"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <polygon style="fill:#BFDCFF;" points="0,66.783 0,512 256,512 278.261,44.522 "></polygon> <polygon style="fill:#8BC0FF;" points="512,66.783 256,44.522 256,512 512,512 "></polygon> <polygon style="fill:#446080;" points="256,0 0,0 0,66.783 256,66.783 278.261,33.391 "></polygon> <polygon style="fill:#324860;" points="422.957,66.783 445.217,33.391 422.957,0 256,0 256,66.783 "></polygon> <rect x="422.957" style="fill:#FF562B;" width="89.043" height="66.783"></rect> <path style="fill:#8BC0FF;" d="M256,150.261V116.87c-95.128,0-172.522,77.392-172.522,172.522S160.871,461.913,256,461.913v-33.391 c-13.952,0-28.591-13.272-40.16-36.411c-11.624-23.247-18.759-53.333-20.578-86.024H256v-33.391h-60.738 c1.82-32.692,8.954-62.777,20.578-86.024C227.409,163.533,242.048,150.261,256,150.261z M185.974,407.043 c0.609,1.218,1.229,2.413,1.854,3.593c-37.718-21.29-64.561-59.666-69.954-104.549h43.938 C163.728,344.005,172.135,379.365,185.974,407.043z M117.875,272.696c5.393-44.883,32.236-83.259,69.954-104.549 c-0.626,1.18-1.245,2.375-1.854,3.593c-13.838,27.678-22.246,63.038-24.16,100.956H117.875z"></path> <path style="fill:#3897FF;" d="M256,116.87h-16.696v345.043H256c95.128,0,172.522-77.392,172.522-172.522S351.128,116.87,256,116.87 z M394.131,272.696h-43.945c-1.913-37.918-10.321-73.278-24.16-100.956c-0.611-1.221-1.232-2.42-1.86-3.603 C361.889,189.427,388.739,227.807,394.131,272.696z M272.696,422.46V306.087h44.042c-1.82,32.692-8.954,62.777-20.578,86.024 C289.181,406.069,281.084,416.43,272.696,422.46z M272.696,272.696V156.322c8.388,6.029,16.485,16.391,23.464,30.349 c11.624,23.247,18.759,53.333,20.578,86.024H272.696z M324.166,410.646c0.628-1.183,1.249-2.382,1.86-3.603 c13.839-27.678,22.246-63.038,24.16-100.956h43.945C388.739,350.976,361.889,389.357,324.166,410.646z"></path> </g></svg>
                                    </div>
                                    <div>
                                        <h2 class="text-xl font-semibold text-black">ACCESOS DIRECTOS</h2>
                                        
                                    </div>
                                </a>
                            </div>

                            <a  id="4"
                                href="https://cloud.pjud.cl/index.php/s/8KcPr6jMt3fbpw4"
                                class="flex items-center gap-4 rounded-lg bg-white p-6 shadow-[0px_14px_34px_0px_rgba(0,0,0,0.08)] ring-1 ring-white/[0.05] transition duration-300 hover:text-black/70 hover:ring-black/20 focus:outline-none focus-visible:ring-[#FF2D20] lg:pb-10 dark:bg-zinc-900 dark:ring-zinc-800 dark:hover:text-white/70 dark:hover:ring-zinc-700 dark:focus-visible:ring-[#FF2D20]"
                            >
                                <div class="flex size-12 shrink-0 items-center justify-center rounded-full bg-[#FF2D20]/10 sm:size-16">
                                <svg fill="#FFFFFF" width="214px" height="214px" viewBox="-2.4 -2.4 28.80 28.80" role="img" xmlns="http://www.w3.org/2000/svg" stroke="#FFFFFF" stroke-width="0.00024000000000000003"><g id="SVGRepo_bgCarrier" stroke-width="0"><rect x="-2.4" y="-2.4" width="28.80" height="28.80" rx="14.4" fill="#2D8CFF" strokewidth="0"></rect></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round" stroke="#CCCCCC" stroke-width="0.9600000000000002"></g><g id="SVGRepo_iconCarrier"><title>Zoom icon</title><path d="M4.587 13.63l-.27-.012H1.89l3.235-3.235-.013-.27a.815.815 0 0 0-.795-.795l-.27-.013H.004l.014.27c.034.438.353.77.794.795l.27.013H3.51L.273 13.618l.014.269c.015.433.362.78.795.796l.27.013h4.044l-.014-.27c-.036-.443-.35-.767-.795-.795zm3.237-4.325H7.82a2.695 2.695 0 1 0 .003 0zm1.141 3.839a1.618 1.618 0 1 1-2.288-2.287 1.618 1.618 0 0 1 2.288 2.287zm12.872-3.838a2.157 2.157 0 0 0-1.615.729 2.152 2.152 0 0 0-1.618-.731 2.147 2.147 0 0 0-1.208.37c-.21-.233-.68-.37-.948-.37v5.392l.27-.013c.45-.03.777-.349.795-.796l.013-.27V11.73l.014-.27c.01-.202.04-.382.132-.54a1.078 1.078 0 0 1 1.473-.393 1.078 1.078 0 0 1 .392.392c.093.16.12.339.132.54l.014.271v1.887l.013.269c.027.44.35.768.795.796l.27.013V11.73l.012-.27c.01-.2.04-.384.134-.543.299-.514.959-.69 1.473-.39a1.078 1.078 0 0 1 .392.393c.092.16.12.343.131.54l.014.27v1.887l.013.269c.028.443.35.77.796.796l.27.013V11.46a2.157 2.157 0 0 0-2.16-2.155zm-10.26.788a2.696 2.696 0 1 0 3.81 3.813 2.696 2.696 0 0 0-3.81-3.813zm3.049 3.05a1.618 1.618 0 1 1-2.288-2.288 1.618 1.618 0 0 1 2.288 2.288z"></path></g></svg>
                                </div>

                                <div class="pt-3 sm:pt-5">
                                    <h2 class="text-xl font-semibold text-black dark:text-white">PROGRAMACION DIARIA</h2>

                                    <p class="mt-4 text-sm/relaxed">
                                    PRECIONE PARA DESPLEGAR TODA LA INFORMACION 📋
                                    </p>
                                </div>

                                
                            </a>

                            <div class="grid grid-cols-2 gap-4">
                                <a  id="5"
                                    href="https://cloud.pjud.cl/index.php/s/Tt5CXjpLT6ZCkjc" 
                                    class="flex items-center gap-4 rounded-lg bg-white p-6 shadow-[0px_14px_34px_0px_rgba(0,0,0,0.08)] ring-1 ring-white/[0.05] transition duration-300 hover:text-black/70 hover:ring-black/20 focus:outline-none focus-visible:ring-[#FF2D20] lg:pb-10 dark:bg-zinc-900 dark:ring-zinc-800 dark:hover:text-white/70 dark:hover:ring-zinc-700 dark:focus-visible:ring-[#FF2D20]">
                                    <div class="size-12 shrink-0 flex items-center justify-center rounded-full bg-red-200">
                                    <svg viewBox="0 0 1024 1024" class="icon" version="1.1" xmlns="http://www.w3.org/2000/svg" fill="#000000"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"><path d="M832 960H192s-64-2.133333-64-170.666667h768c0 168.533333-64 170.666667-64 170.666667z" fill="#42A5F5"></path><path d="M170.666667 64h682.666666v725.333333H170.666667z" fill="#90CAF9"></path><path d="M384 320h341.333333v42.666667H384zM384 405.333333h341.333333v42.666667H384zM384 490.666667h341.333333v42.666666H384zM384 576h341.333333v42.666667H384zM384 661.333333h341.333333v42.666667H384z" fill="#1976D2"></path><path d="M298.666667 320h42.666666v42.666667h-42.666666zM298.666667 405.333333h42.666666v42.666667h-42.666666zM298.666667 490.666667h42.666666v42.666666h-42.666666zM298.666667 576h42.666666v42.666667h-42.666666zM298.666667 661.333333h42.666666v42.666667h-42.666666z" fill="#1976D2"></path></g></svg>
                                    </div>
                                    <div class="">
                                        <h2 class="text-xl font-semibold text-black">PROCEDIMIENTOS TRIBUNAL</h2>
                                        
                                    </div>
                                </a>

                                <a  id="6"
                                    href="https://cloud.pjud.cl/index.php/s/gMbJDECybpg4Hb8" 
                                    class="flex items-center gap-4 rounded-lg bg-white p-6 shadow-[0px_14px_34px_0px_rgba(0,0,0,0.08)] ring-1 ring-white/[0.05] transition duration-300 hover:text-black/70 hover:ring-black/20 focus:outline-none focus-visible:ring-[#FF2D20] lg:pb-10 dark:bg-zinc-900 dark:ring-zinc-800 dark:hover:text-white/70 dark:hover:ring-zinc-700 dark:focus-visible:ring-[#FF2D20]">
                                    <div class="size-12 shrink-0 flex items-center justify-center rounded-full bg-blue-200">
                                        <svg height="200px" width="200px" version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 512.001 512.001" xml:space="preserve" fill="#000000"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path style="fill:#0084FF;" d="M332.803,10.449H95.726c-22.275,0-40.334,18.06-40.334,40.335v410.434 c0,22.276,18.059,40.334,40.334,40.334h320.546c22.275,0,40.334-18.059,40.334-40.334V134.253L332.803,10.449z"></path> <path style="fill:#10BAFC;" d="M373.139,134.253h83.467L332.806,10.451v83.47C332.803,116.195,350.861,134.253,373.139,134.253z"></path> <path d="M463.996,126.864L340.192,3.061C338.231,1.101,335.574,0,332.803,0H95.726C67.724,0,44.944,22.782,44.944,50.784v410.434 c0,28.001,22.781,50.783,50.783,50.783h320.546c28.002,0,50.783-22.781,50.783-50.783V134.253 C467.056,131.482,465.955,128.824,463.996,126.864z M343.255,35.679l88.127,88.126H373.14c-7.984,0-15.49-3.109-21.134-8.753 c-5.643-5.643-8.752-13.148-8.751-21.131L343.255,35.679L343.255,35.679z M416.274,491.102H95.726 c-16.479,0-29.885-13.406-29.885-29.885V50.784c0.001-16.479,13.407-29.886,29.885-29.886h226.631v73.021 c-0.002,13.565,5.28,26.318,14.871,35.909c9.592,9.592,22.345,14.874,35.911,14.874h73.018v316.515 C446.158,477.696,432.752,491.102,416.274,491.102z"></path> <path d="M275.092,351.492h-4.678c-5.77,0-10.449,4.678-10.449,10.449c0,5.771,4.679,10.449,10.449,10.449h4.678 c5.77,0,10.449-4.678,10.449-10.449C285.541,356.17,280.862,351.492,275.092,351.492z"></path> <path d="M236.61,351.492H135.118c-5.77,0-10.449,4.678-10.449,10.449c0,5.771,4.679,10.449,10.449,10.449H236.61 c5.77,0,10.449-4.678,10.449-10.449C247.059,356.17,242.381,351.492,236.61,351.492z"></path> <path d="M376.882,303.747H135.119c-5.77,0-10.449,4.678-10.449,10.449c0,5.771,4.679,10.449,10.449,10.449h241.763 c5.77,0,10.449-4.678,10.449-10.449C387.331,308.425,382.652,303.747,376.882,303.747z"></path> <path d="M376.882,256H135.119c-5.77,0-10.449,4.678-10.449,10.449s4.679,10.449,10.449,10.449h241.763 c5.77,0,10.449-4.678,10.449-10.449S382.652,256,376.882,256z"></path> <path d="M376.882,208.255H135.119c-5.77,0-10.449,4.678-10.449,10.449s4.679,10.449,10.449,10.449h241.763 c5.77,0,10.449-4.678,10.449-10.449C387.331,212.933,382.652,208.255,376.882,208.255z"></path> </g></svg>
                                    </div>
                                    <div>
                                        <h2 class="text-xl font-semibold text-black">FORMULARIOS</h2>
                                        
                                    </div>
                                </a>
                            </div>
                        </div>
                        <div class="grid gap-6 pt-6 lg:grid-cols-2 lg:gap-8">
                        <div class="flip-container" onclick="toggleFlip(this)">
                                <div class="flipper">
                                <a  id="7"
                                    
                                    class="flex front items-center justify-center gap-4 rounded-lg bg-white p-6 shadow-[0px_14px_34px_0px_rgba(0,0,0,0.08)] ring-1 ring-white/[0.05] transition duration-300 hover:text-black/70 hover:ring-black/20 focus:outline-none focus-visible:ring-[#FF2D20] lg:pb-10 dark:bg-zinc-900 dark:ring-zinc-800 dark:hover:text-white/70 dark:hover:ring-zinc-700 dark:focus-visible:ring-[#FF2D20]"
                                >
                                    <div class="flex size-12 shrink-0 items-center justify-center rounded-full sm:size-16">
                                    <svg viewBox="0 0 1024 1024" class="icon" version="1.1" xmlns="http://www.w3.org/2000/svg" fill="#000000"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"><path d="M106.666667 810.666667V298.666667h810.666666v512c0 46.933333-38.4 85.333333-85.333333 85.333333H192c-46.933333 0-85.333333-38.4-85.333333-85.333333z" fill="#CFD8DC"></path><path d="M917.333333 213.333333v128H106.666667v-128c0-46.933333 38.4-85.333333 85.333333-85.333333h640c46.933333 0 85.333333 38.4 85.333333 85.333333z" fill="#F44336"></path><path d="M704 213.333333m-64 0a64 64 0 1 0 128 0 64 64 0 1 0-128 0Z" fill="#B71C1C"></path><path d="M320 213.333333m-64 0a64 64 0 1 0 128 0 64 64 0 1 0-128 0Z" fill="#B71C1C"></path><path d="M704 64c-23.466667 0-42.666667 19.2-42.666667 42.666667v106.666666c0 23.466667 19.2 42.666667 42.666667 42.666667s42.666667-19.2 42.666667-42.666667V106.666667c0-23.466667-19.2-42.666667-42.666667-42.666667zM320 64c-23.466667 0-42.666667 19.2-42.666667 42.666667v106.666666c0 23.466667 19.2 42.666667 42.666667 42.666667s42.666667-19.2 42.666667-42.666667V106.666667c0-23.466667-19.2-42.666667-42.666667-42.666667z" fill="#B0BEC5"></path><path d="M277.333333 426.666667h85.333334v85.333333h-85.333334zM405.333333 426.666667h85.333334v85.333333h-85.333334zM533.333333 426.666667h85.333334v85.333333h-85.333334zM661.333333 426.666667h85.333334v85.333333h-85.333334zM277.333333 554.666667h85.333334v85.333333h-85.333334zM405.333333 554.666667h85.333334v85.333333h-85.333334zM533.333333 554.666667h85.333334v85.333333h-85.333334zM661.333333 554.666667h85.333334v85.333333h-85.333334zM277.333333 682.666667h85.333334v85.333333h-85.333334zM405.333333 682.666667h85.333334v85.333333h-85.333334zM533.333333 682.666667h85.333334v85.333333h-85.333334zM661.333333 682.666667h85.333334v85.333333h-85.333334z" fill="#90A4AE"></path></g></svg>
                                    </div>

                                    <div class="pt-3 sm:pt-5">
                                        <h2 class="text-xl font-semibold text-black dark:text-white">TURNO SEMANAL</h2>
                                        <p class="mt-2 text-sm/relaxed">
                                            Precione aca 👇🏻
                                        </p>
                                    </div>
                                    <svg class="size-6 shrink-0 self-center stroke-[#FF2D20]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12h15m0 0l-6.75-6.75M19.5 12l-6.75 6.75"/></svg>
                                </a>
                                <a  id="7" class="flex back items-center justify-center gap-4 rounded-lg bg-white p-6 shadow-[0px_14px_34px_0px_rgba(0,0,0,0.08)] ring-1 ring-white/[0.05] transition duration-300 hover:text-black/70 hover:ring-black/20 focus:outline-none focus-visible:ring-[#FF2D20] lg:pb-10 dark:bg-zinc-900 dark:ring-zinc-800 dark:hover:text-white/70 dark:hover:ring-zinc-700 dark:focus-visible:ring-[#FF2D20]">
                                     <!-- Columna 1 -->
                                     <div class="space-y-2">
                                        <h3 class="text-lg font-semibold text-[#FF2D20] border-b pb-1 mb-2">MAGISTRADOS</h3>
                                        <ul class="space-y-1">
                                            <li class="text-sm dark:text-gray-300">MAG 1</li>
                                            <li class="text-sm dark:text-gray-300">MAG 2</li>
                                            <li class="text-sm dark:text-gray-300">MAG 3</li>
                                        </ul>
                                    </div>
                                    <!-- Columna 2 -->
                                    <div class="space-y-2">
                                        <h3 class="text-lg font-semibold text-[#FF2D20] border-b pb-1 mb-2">FUNCIONARIOS</h3>
                                        <ul class="space-y-1">
                                            <li class="text-sm dark:text-gray-300">FUN 1</li>
                                            <li class="text-sm dark:text-gray-300">FUN 2</li>
                                            <li class="text-sm dark:text-gray-300">FUN 3</li>
                                        </ul>
                                    </div>
                                    <!-- Columna 3 -->
                                    <div class="space-y-2">
                                        <h3 class="text-lg font-semibold text-[#FF2D20] border-b pb-1 mb-2">JEFATURA</h3>
                                        <ul class="space-y-1">
                                            <li class="text-sm dark:text-gray-300">JEF 1</li>
                                            <li class="text-sm dark:text-gray-300">ACA PODRIAN</li>
                                            <li class="text-sm dark:text-gray-300">PONER EL ADICIONAL DE MAG</li>
                                        </ul>
                                    </div>   
                                </a>
                                
                                </div>
                            </div>
                            <div class="flip-container" onclick="toggleFlip(this)">
                                <div class="flipper">
                                <a  id="7"
                                    
                                    class="flex front items-center justify-center gap-4 rounded-lg bg-white p-6 shadow-[0px_14px_34px_0px_rgba(0,0,0,0.08)] ring-1 ring-white/[0.05] transition duration-300 hover:text-black/70 hover:ring-black/20 focus:outline-none focus-visible:ring-[#FF2D20] lg:pb-10 dark:bg-zinc-900 dark:ring-zinc-800 dark:hover:text-white/70 dark:hover:ring-zinc-700 dark:focus-visible:ring-[#FF2D20]"
                                >
                                    <div class="flex size-12 shrink-0 items-center justify-center rounded-full sm:size-16">
                                    <svg viewBox="0 0 1024 1024" class="icon" version="1.1" xmlns="http://www.w3.org/2000/svg" fill="#000000"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"><path d="M106.666667 810.666667V298.666667h810.666666v512c0 46.933333-38.4 85.333333-85.333333 85.333333H192c-46.933333 0-85.333333-38.4-85.333333-85.333333z" fill="#CFD8DC"></path><path d="M917.333333 213.333333v128H106.666667v-128c0-46.933333 38.4-85.333333 85.333333-85.333333h640c46.933333 0 85.333333 38.4 85.333333 85.333333z" fill="#F44336"></path><path d="M704 213.333333m-64 0a64 64 0 1 0 128 0 64 64 0 1 0-128 0Z" fill="#B71C1C"></path><path d="M320 213.333333m-64 0a64 64 0 1 0 128 0 64 64 0 1 0-128 0Z" fill="#B71C1C"></path><path d="M704 64c-23.466667 0-42.666667 19.2-42.666667 42.666667v106.666666c0 23.466667 19.2 42.666667 42.666667 42.666667s42.666667-19.2 42.666667-42.666667V106.666667c0-23.466667-19.2-42.666667-42.666667-42.666667zM320 64c-23.466667 0-42.666667 19.2-42.666667 42.666667v106.666666c0 23.466667 19.2 42.666667 42.666667 42.666667s42.666667-19.2 42.666667-42.666667V106.666667c0-23.466667-19.2-42.666667-42.666667-42.666667z" fill="#B0BEC5"></path><path d="M277.333333 426.666667h85.333334v85.333333h-85.333334zM405.333333 426.666667h85.333334v85.333333h-85.333334zM533.333333 426.666667h85.333334v85.333333h-85.333334zM661.333333 426.666667h85.333334v85.333333h-85.333334zM277.333333 554.666667h85.333334v85.333333h-85.333334zM405.333333 554.666667h85.333334v85.333333h-85.333334zM533.333333 554.666667h85.333334v85.333333h-85.333334zM661.333333 554.666667h85.333334v85.333333h-85.333334zM277.333333 682.666667h85.333334v85.333333h-85.333334zM405.333333 682.666667h85.333334v85.333333h-85.333334zM533.333333 682.666667h85.333334v85.333333h-85.333334zM661.333333 682.666667h85.333334v85.333333h-85.333334z" fill="#90A4AE"></path></g></svg>
                                    </div>

                                    <div class="pt-3 sm:pt-5">
                                        <h2 class="text-xl font-semibold text-black dark:text-white">TURNO ACD</h2>
                                        <p class="mt-2 text-sm/relaxed">
                                            Precione aca 👇🏻
                                        </p>
                                    </div>
                                    <svg class="size-6 shrink-0 self-center stroke-[#FF2D20]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12h15m0 0l-6.75-6.75M19.5 12l-6.75 6.75"/></svg>
                                </a>
                                <a  id="7" class="flex back items-center justify-center gap-4 rounded-lg bg-white p-6 shadow-[0px_14px_34px_0px_rgba(0,0,0,0.08)] ring-1 ring-white/[0.05] transition duration-300 hover:text-black/70 hover:ring-black/20 focus:outline-none focus-visible:ring-[#FF2D20] lg:pb-10 dark:bg-zinc-900 dark:ring-zinc-800 dark:hover:text-white/70 dark:hover:ring-zinc-700 dark:focus-visible:ring-[#FF2D20]">
                                     <!-- Columna 1 -->
                                    <div class="space-y-2">
                                        <h3 class="text-lg font-semibold text-[#FF2D20] border-b pb-1 mb-2">MAGISTRADOS</h3>
                                        <ul class="space-y-1">
                                            <li class="text-sm dark:text-gray-300">MAG 1</li>
                                            <li class="text-sm dark:text-gray-300">MAG 2</li>
                                            <li class="text-sm dark:text-gray-300">MAG 3</li>
                                        </ul>
                                    </div>
                                    <!-- Columna 2 -->
                                    <div class="space-y-2">
                                        <h3 class="text-lg font-semibold text-[#FF2D20] border-b pb-1 mb-2">FUNCIONARIOS</h3>
                                        <ul class="space-y-1">
                                            <li class="text-sm dark:text-gray-300">Jorge Andres Troncoso Reyes</li>
                                            <li class="text-sm dark:text-gray-300">FUN 2</li>
                                            <li class="text-sm dark:text-gray-300">FUN 3</li>
                                        </ul>
                                    </div>
                                    <!-- Columna 3 -->
                                    <div class="space-y-2">
                                        <h3 class="text-lg font-semibold text-[#FF2D20] border-b pb-1 mb-2">JEFATURA</h3>
                                        <ul class="space-y-1">
                                            <li class="text-sm dark:text-gray-300">JEF 1</li>
                                            <li class="text-sm dark:text-gray-300">ACA PODRIAN</li>
                                            <li class="text-sm dark:text-gray-300">PONER EL ADICIONAL DE MAG</li>
                                        </ul>
                                    </div>   
                                </a>
                                
                                </div>
                            </div>



                        </div>
                        <div class="grid gap-6 pt-6 lg:grid-cols-3 lg:gap-8">
                        <a
                                href="http://www2.intranet.pjud/"
                                class="flex items-start gap-4 rounded-lg bg-white p-6 shadow-[0px_14px_34px_0px_rgba(0,0,0,0.08)] ring-1 ring-white/[0.05] transition duration-300 hover:text-black/70 hover:ring-black/20 focus:outline-none focus-visible:ring-[#FF2D20] lg:pb-10 dark:bg-zinc-900 dark:ring-zinc-800 dark:hover:text-white/70 dark:hover:ring-zinc-700 dark:focus-visible:ring-[#FF2D20]"
                            >
                                <div class="flex size-12 shrink-0 items-center justify-center rounded-full bg-[#FF2D20]/10 sm:size-16">
                                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path d="M2.99902 3L20.999 21M9.8433 9.91364C9.32066 10.4536 8.99902 11.1892 8.99902 12C8.99902 13.6569 10.3422 15 11.999 15C12.8215 15 13.5667 14.669 14.1086 14.133M6.49902 6.64715C4.59972 7.90034 3.15305 9.78394 2.45703 12C3.73128 16.0571 7.52159 19 11.9992 19C13.9881 19 15.8414 18.4194 17.3988 17.4184M10.999 5.04939C11.328 5.01673 11.6617 5 11.9992 5C16.4769 5 20.2672 7.94291 21.5414 12C21.2607 12.894 20.8577 13.7338 20.3522 14.5" stroke="#000000" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path> </g></svg>
                                </div>

                                <div class="pt-3 sm:pt-5">
                                    <h2 class="text-xl font-semibold text-black dark:text-white">PROXIMAMENTE</h2>

                                    <p class="mt-4 text-sm/relaxed">
                                        AGENDA DIARIA
                                    </p>
                                </div>

                                <svg class="size-6 shrink-0 self-center stroke-[#FF2D20]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12h15m0 0l-6.75-6.75M19.5 12l-6.75 6.75"/></svg>
                            </a>  
                            <a
                                href="http://www2.intranet.pjud/"
                                class="flex items-start gap-4 rounded-lg bg-white p-6 shadow-[0px_14px_34px_0px_rgba(0,0,0,0.08)] ring-1 ring-white/[0.05] transition duration-300 hover:text-black/70 hover:ring-black/20 focus:outline-none focus-visible:ring-[#FF2D20] lg:pb-10 dark:bg-zinc-900 dark:ring-zinc-800 dark:hover:text-white/70 dark:hover:ring-zinc-700 dark:focus-visible:ring-[#FF2D20]"
                            >
                                <div class="flex size-12 shrink-0 items-center justify-center rounded-full bg-[#FF2D20]/10 sm:size-16">
                                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path d="M2.99902 3L20.999 21M9.8433 9.91364C9.32066 10.4536 8.99902 11.1892 8.99902 12C8.99902 13.6569 10.3422 15 11.999 15C12.8215 15 13.5667 14.669 14.1086 14.133M6.49902 6.64715C4.59972 7.90034 3.15305 9.78394 2.45703 12C3.73128 16.0571 7.52159 19 11.9992 19C13.9881 19 15.8414 18.4194 17.3988 17.4184M10.999 5.04939C11.328 5.01673 11.6617 5 11.9992 5C16.4769 5 20.2672 7.94291 21.5414 12C21.2607 12.894 20.8577 13.7338 20.3522 14.5" stroke="#000000" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path> </g></svg>
                                </div>

                                <div class="pt-3 sm:pt-5">
                                    <h2 class="text-xl font-semibold text-black dark:text-white">PROXIMAMENTE</h2>

                                    <p class="mt-4 text-sm/relaxed">
                                        RUEDA DE ESTACIONAMIENTO NO VA
                                    </p>
                                </div>

                                <svg class="size-6 shrink-0 self-center stroke-[#FF2D20]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12h15m0 0l-6.75-6.75M19.5 12l-6.75 6.75"/></svg>
                            </a>
                            <a
                                href="http://www2.intranet.pjud/"
                                class="flex items-start gap-4 rounded-lg bg-white p-6 shadow-[0px_14px_34px_0px_rgba(0,0,0,0.08)] ring-1 ring-white/[0.05] transition duration-300 hover:text-black/70 hover:ring-black/20 focus:outline-none focus-visible:ring-[#FF2D20] lg:pb-10 dark:bg-zinc-900 dark:ring-zinc-800 dark:hover:text-white/70 dark:hover:ring-zinc-700 dark:focus-visible:ring-[#FF2D20]"
                            >
                                <div class="flex size-12 shrink-0 items-center justify-center rounded-full bg-[#FF2D20]/10 sm:size-16">
                                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path d="M2.99902 3L20.999 21M9.8433 9.91364C9.32066 10.4536 8.99902 11.1892 8.99902 12C8.99902 13.6569 10.3422 15 11.999 15C12.8215 15 13.5667 14.669 14.1086 14.133M6.49902 6.64715C4.59972 7.90034 3.15305 9.78394 2.45703 12C3.73128 16.0571 7.52159 19 11.9992 19C13.9881 19 15.8414 18.4194 17.3988 17.4184M10.999 5.04939C11.328 5.01673 11.6617 5 11.9992 5C16.4769 5 20.2672 7.94291 21.5414 12C21.2607 12.894 20.8577 13.7338 20.3522 14.5" stroke="#000000" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path> </g></svg>
                                </div>

                                <div class="pt-3 sm:pt-5">
                                    <h2 class="text-xl font-semibold text-black dark:text-white">PROXIMAMENTE</h2>

                                    <p class="mt-4 text-sm/relaxed">
                                        UBICACION GEOGRAFICA DEL TRIBUNAL
                                    </p>
                                </div>

                                <svg class="size-6 shrink-0 self-center stroke-[#FF2D20]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12h15m0 0l-6.75-6.75M19.5 12l-6.75 6.75"/></svg>
                            </a>
                            
                        </div>   
                    </main>

                    <footer class="py-16 text-center text-sm text-black dark:text-white/70">
                        
                        Pagina web desarrollada por Jorge Troncoso
                    </footer>
                </div>
            </div>
        </div>
        <script src="/js/index.js"></script>
    </body>
</html>
