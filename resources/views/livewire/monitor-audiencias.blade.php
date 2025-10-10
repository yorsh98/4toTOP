<div class="w-full h-full" x-data="{
    rotationInterval: null,
    countdown: @js($rotationInterval),
    init() {
        // Iniciar rotación automáticamente
        this.startRotation(@js($rotationInterval));
        
        // Escuchar eventos
        window.addEventListener('startRotationJS', (event) => {
            this.startRotation(event.detail.duration);
        });
        window.addEventListener('stopRotationJS', () => {
            this.stopRotation();
        });
    },
    startRotation(duration) {
        this.stopRotation();
        this.countdown = duration; // Reiniciar contador
        
        // Usar un solo intervalo para ambas funciones
        this.rotationInterval = setInterval(() => {
            this.countdown--;
            
            if(this.countdown <= 0) {
                @this.rotate();
                this.countdown = duration; // Reiniciar contador al rotar
            }
        }, 1000); // Intervalo de 1 segundo para mayor precisión
    },
    stopRotation() {
        if (this.rotationInterval) {
            clearInterval(this.rotationInterval);
            this.rotationInterval = null;
        }
    }
}">
    <!-- Contenedor grid que usa todo el espacio -->
    <div class="grid grid-cols-1 lg:grid-cols-1 xl:grid-cols-1 gap-4 h-full">
        <!-- Tarjeta de tipo de audiencia (siempre visible) -->
        <div class="bg-white rounded-xl shadow-md overflow-hidden transition-all duration-300 h-full flex flex-col">
            <!-- Encabezado de sección -->
            <div class="@if($currentType == 'Juicio Oral' || $currentType == 'Cont. Juicio Oral') bg-blue-600 @elseif($currentType == 'Audiencia Corta') bg-lime-700 @else bg-purple-600 @endif text-white px-4 py-3 flex items-center gap-2">
                <i class="fas @if($currentType == 'Juicio Oral' || $currentType == 'Cont. Juicio Oral') fa-balance-scale @elseif($currentType == 'Audiencia Corta') fa-stopwatch @else fa-file-alt @endif"></i>
                <h3 class="text-lg font-bold">{{ $currentType }}</h3>
            </div>
            
            <!-- Contenido de audiencias -->
            <div class="p-2 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-2 flex-1 overflow-y-auto">
                <!-- Mensaje cuando no hay audiencias -->
                @if($audiencias->isEmpty())
                    <div class="col-span-full text-center py-8 text-gray-500 italic bg-gray-50 rounded-lg border-2 border-dashed border-gray-200">
                        <i class="fas fa-exclamation-circle text-3xl mb-3 text-gray-400"></i>
                        <p class="text-lg font-medium">No se encuentran programadas audiencias de este tipo para este día</p>
                    </div>
                @else
                    @foreach($audiencias->first() as $audiencia)
                        <div class="border border-gray-200 rounded-lg overflow-hidden">
                            <!-- Encabezado de audiencia -->
                            <div class="@if($currentType == 'Juicio Oral' || $currentType == 'Cont. Juicio Oral') bg-blue-500 
                                        @elseif($currentType == 'Audiencia Corta') bg-lime-600 
                                        @else bg-purple-500 @endif 
                                        text-white px-3 py-2 flex gap-2 text-sm">
                                <!-- Icono centrado verticalmente entre las líneas -->
                                <div class="flex items-center">
                                    <i class="fas fa-door-open"></i>
                                </div>
                                <!-- Texto en dos líneas, centrado -->
                                <div class="flex flex-col justify-center flex-1">
                                    <span class="block font-bold text-center">
                                        SALA {{ $audiencia->sala }} - {{ $audiencia->ubicacion }} - {{ \Carbon\Carbon::parse($audiencia->hora_inicio)->format('H:i') }} Horas
                                    </span>
                                    <span class="block text-center">
                                        RUC {{ $audiencia->ruc }}
                                    </span>
                                </div>
                            </div>                            
                            <!-- Detalles de audiencia -->
                            <div class="p-3">
                                <div class="grid grid-cols-2 gap-3">
                                    <!-- Tarjeta RIT -->
                                    <div class="bg-gray-50 p-3 rounded-lg border-l-4 
                                        @if($currentType == 'Juicio Oral' || $currentType == 'Cont. Juicio Oral') border-blue-500 
                                        @elseif($currentType == 'Audiencia Corta') border-lime-500 
                                        @else border-purple-500 
                                        @endif">
                                        <div class="flex items-center gap-3">
                                            <i class="fas fa-pen-alt text-lg 
                                                @if($currentType == 'Juicio Oral' || $currentType == 'Cont. Juicio Oral') text-blue-500 
                                                @elseif($currentType == 'Audiencia Corta') text-lime-500 
                                                @else text-purple-500 
                                                @endif"></i>
                                            <div class="font-bold">
                                                <h4 class="text-xs text-gray-500 mb-1">RIT</h4>
                                                <p class="text-gray-800 text-sm">{{ $audiencia->rit }}</p>
                                            </div>
                                        </div>
                                    </div>                                    
                                    <!-- Tarjeta ESTADO -->
                                    <div 
                                        x-data="{ isBlinking: @js(in_array($audiencia->estado, ['POR_REALIZARSE', 'EN_CURSO', 'RECESO', 'FINALIZADA'])) }"
                                        x-init="if(isBlinking) {
                                            setInterval(() => {
                                                isBlinking = !isBlinking;
                                            }, 1000);
                                        }"
                                        class="bg-gray-50 p-3 rounded-lg border-l-4 
                                            @if($currentType == 'Juicio Oral' || $currentType == 'Cont. Juicio Oral') border-blue-500 
                                            @elseif($currentType == 'Audiencia Corta') border-lime-600 
                                            @else border-purple-500 
                                            @endif"
                                        :class="{ 
                                            'bg-blue-50': isBlinking,
                                            'ring-2 ring-blue-300': isBlinking,
                                            'animate-pulse': isBlinking
                                        }">
                                        <div class="flex items-center gap-3">
                                            <i class="fas fa-video text-lg 
                                            @if($audiencia->estado == 'POR_REALIZARSE') text-red-500
                                            @elseif($audiencia->estado == 'EN_CURSO') text-green-600
                                            @elseif($audiencia->estado == 'RECESO') text-blue-500
                                            @else text-red-500
                                            @endif"
                                            :class="{ 'animate-pulse': isBlinking }"></i>
                                            <div>
                                                <h4 class="text-xs text-gray-500 font-medium mb-1">ESTADO</h4>
                                                <p class="font-bold text-sm" :class="{ 'animate-pulse': isBlinking }">
                                                    @switch($audiencia->estado)
                                                        @case('POR_REALIZARSE') 
                                                            <span :class="{ 'text-red-600': !isBlinking, 'text-red-600': isBlinking }">
                                                                POR INICIAR
                                                            </span> @break
                                                        @case('EN_CURSO') 
                                                            <span :class="{ 'text-lime-600': !isBlinking, 'text-lime-600': isBlinking }">
                                                                EN CURSO
                                                            </span> @break
                                                        @case('RECESO') 
                                                            <span :class="{ 'text-blue-600': !isBlinking, 'text-blue-700': isBlinking }">
                                                                RECESO
                                                            </span> @break
                                                        @case('FINALIZADA') 
                                                            <span :class="{ 'text-red-600': !isBlinking, 'text-red-600': isBlinking }">
                                                                FINALIZADA
                                                            </span> @break
                                                    @endswitch
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>
    </div>    
    <!-- Indicador de rotación -->
    <div class="fixed bottom-4 right-4 bg-black bg-opacity-70 text-white px-3 py-1 rounded-full text-sm">
        Mostrando: {{ $currentType }} | Próximo cambio en: 
        <span x-text="countdown">{{ $rotationInterval }}</span>s
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Función para verificar y actualizar estados automáticamente
            const checkAndAutoUpdateAudienciasCortas = () => {
                const now = new Date();
                const hours = now.getHours();
                const minutes = now.getMinutes();
                const time = `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}`;

                // Solo actuar si estamos en un rango razonable (ej. entre 12:00 y 16:00)
                if (hours < 12 || hours > 16) return;

                // Llamada a Livewire para actualizar estados según la hora
                if (time === '12:30') {
                    @this.actualizarEstadoAudienciasCortas('EN_CURSO');
                } else if (time === '12:35') {
                    @this.actualizarEstadoAudienciasCortas('FINALIZADA');
                }
            };
            // Ejecutar cada minuto (para no perder el minuto exacto)
            setInterval(checkAndAutoUpdateAudienciasCortas, 60000); // Cada 60 segundos
            // Ejecutar inmediatamente al cargar
            checkAndAutoUpdateAudienciasCortas();
        });
    </script>
</div>