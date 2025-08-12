<div class="w-full h-full">
    <!-- Contenedor grid que usa todo el espacio -->
    <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-2 gap-4 h-full">
        @foreach($audiencias as $tipo => $grupo)
            <!-- Tarjeta de tipo de audiencia -->
            <div class="bg-white rounded-xl shadow-md overflow-hidden transition-all duration-300 h-full flex flex-col">
                <!-- Encabezado de sección -->
                <div class="@if($tipo == 'Juicio Oral' || $tipo == 'Cont. Juicio Oral') bg-blue-600 @elseif($tipo == 'Audiencia Corta') bg-green-600 @else bg-purple-600 @endif text-white px-4 py-3 flex items-center gap-2">
                    <i class="fas @if($tipo == 'Juicio Oral' || $tipo == 'Cont. Juicio Oral') fa-balance-scale @elseif($tipo == 'Audiencia Corta') fa-stopwatch @else fa-file-alt @endif"></i>
                    <h3 class="text-lg font-bold">{{ $tipo }}</h3>
                </div>
                
                <!-- Contenido de audiencias -->
                <div class="p-2 space-y-2 flex-1 overflow-y-auto">
                    @foreach($grupo as $audiencia)
                        <div class="border border-gray-200 rounded-lg overflow-hidden">
                            <!-- Encabezado de audiencia -->
                            <div class="@if($tipo == 'Juicio Oral' || $tipo == 'Cont. Juicio Oral') bg-blue-500 @elseif($tipo == 'Audiencia Corta') bg-green-500 @else bg-purple-500 @endif text-white px-3 py-2 flex items-center gap-2 text-sm">
                                <i class="fas @if($tipo == 'Lectura de Sentencia') fa-video @else fa-door-open @endif"></i>
                                <span>Sala {{ $audiencia->sala }} - {{ $audiencia->ubicacion }} - {{ \Carbon\Carbon::parse($audiencia->hora_inicio)->format('H:i') }} Horas - RUC {{ $audiencia->ruc }}</span>
                            </div>
                            
                            <!-- Detalles de audiencia -->
                            <div class="p-3">
                                <div class="grid grid-cols-2 gap-2">
                                    <!-- RIT -->
                                    <div class="bg-gray-50 p-2 rounded-lg border-l-4 @if($tipo == 'Juicio Oral' || $tipo == 'Cont. Juicio Oral') border-blue-500 @elseif($tipo == 'Audiencia Corta') border-green-500 @else border-purple-500 @endif">
                                        <h4 class="text-xs text-gray-500 font-medium">RIT</h4>
                                        <p class="text-gray-800 font-medium text-sm">{{ $audiencia->rit }}</p>
                                    </div>
                                   
                                    <!-- Estado -->
                                    <div class="bg-gray-50 p-2 rounded-lg border-l-4 @if($tipo == 'Juicio Oral' || $tipo == 'Cont. Juicio Oral') border-blue-500 @elseif($tipo == 'Audiencia Corta') border-green-500 @else border-purple-500 @endif">
                                        <h4 class="text-xs text-gray-500 font-medium">ESTADO</h4>
                                        <p class="text-gray-800 font-medium text-sm">
                                            @switch($audiencia->estado)
                                                @case('POR_REALIZARSE') <span class="text-yellow-600">POR REALIZARSE</span> @break
                                                @case('EN_CURSO') <span class="text-blue-600">EN CURSO</span> @break
                                                @case('RECESO') <span class="text-orange-600">RECESO</span> @break
                                                @case('FINALIZADA') <span class="text-green-600">FINALIZADA</span> @break
                                            @endswitch
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</div>