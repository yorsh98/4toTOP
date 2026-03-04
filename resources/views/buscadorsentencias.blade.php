<x-in-layout>
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
                    <svg fill="#000000" viewBox="-1 0 19 19" xmlns="http://www.w3.org/2000/svg" class="cf-icon-svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"><path d="M16.417 9.579A7.917 7.917 0 1 1 8.5 1.662a7.917 7.917 0 0 1 7.917 7.917zm-6.37 2.817a.317.317 0 0 0-.316-.317H4.15a.318.318 0 0 0-.317.317v.715a.318.318 0 0 0 .317.317h5.58a.317.317 0 0 0 .317-.317zM5.362 8.655l2.362 2.362a.396.396 0 0 0 .56-.56L5.921 8.096a.396.396 0 1 0-.56.56zm8.05 3.235-3.285-3.285.81-.81L8.593 5.45 6.398 7.644l2.345 2.344.823-.823 3.285 3.285a.396.396 0 1 0 .56-.56zM9.056 4.96l2.363 2.362a.396.396 0 1 0 .56-.56L9.615 4.4a.396.396 0 1 0-.56.56z"></path></g></svg>
                </div>
                <div class="pt-3 sm:pt-5">
                    <h2 class="text-xl font-semibold text-black dark:text-white">INDICE DE SENTENCIAS 4º TOP</h2>
                </div>
                <span class="absolute bottom-2 right-4 text-sm text-gray-500 dark:text-gray-400">
                    Periodo 2024 a la fecha
                </span>
            </div>
        </div>
        <div class="grid gap-6 lg:grid-cols-1 lg:gap-8 animate__animated animate__fadeInUp animate__delay-0.8s">
            <div class="flex flex-col items-center gap-6 overflow-hidden rounded-lg bg-white p-6 shadow-[0px_14px_34px_0px_rgba(0,0,0,0.08)] ring-1 ring-white/[0.05] transition duration-300 focus:outline-none focus-visible:ring-[#FF2D20] md:row-span-3 lg:p-10 lg:pb-10 dark:bg-zinc-900 dark:ring-zinc-800 dark:hover:text-white/70 dark:hover:ring-zinc-700 dark:focus-visible:ring-[#FF2D20]">                                
                <livewire:buscador-sentencias-tabla />
            </div>
    </div>                                       
</x-in-layout>
