<div class="fo-kiosk-audiencias" x-data="{
    rotationInterval: null,
    countdown: @js($rotationInterval),
    init() {
        // Iniciar rotación automáticamente
        this.startRotation(@js($rotationInterval));
        this.fitRows();
        window.addEventListener('resize', () => this.fitRows());

        // Escuchar eventos
        window.addEventListener('startRotationJS', (event) => {
            this.startRotation(event.detail.duration);
        });
        window.addEventListener('stopRotationJS', () => {
            this.stopRotation();
        });
    },
    fitRows() {
        const rows = [...this.$el.querySelectorAll('tbody tr')].filter((row) => !row.querySelector('.fo-kiosk-empty'));
        const rowHeight = rows.length ? Math.max(...rows.map((row) => row.offsetHeight)) : 90;
        const available = this.$el.clientHeight - this.$el.querySelector('.fo-kiosk-heading').offsetHeight - this.$el.querySelector('thead').offsetHeight - 2;
        const fit = Math.max(1, Math.floor(available / rowHeight));
        if (fit !== $wire.rowsPerView) $wire.set('rowsPerView', fit);
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
    <div class="fo-kiosk-heading">
        <p>Programación de audiencias</p>
        <h2 class="fo-kiosk-type @if(in_array($currentType, ['Juicio Oral', 'Cont. Juicio Oral'])) fo-kiosk-type--juicio @elseif($currentType == 'Audiencia Corta') fo-kiosk-type--corta @else fo-kiosk-type--lectura @endif">{{ $currentType }}@if($views > 1) <small class="fo-kiosk-view">Vista {{ $currentView + 1 }} de {{ $views }}</small>@endif</h2>
        <div class="fo-kiosk-rotation" aria-label="Tipos de audiencia">
            @foreach($audienciaTypes as $type)
                <span class="fo-kiosk-pill @if($currentType === $type) is-active @endif" @if($currentType === $type) aria-current="true" @endif>{{ $type }}</span>
            @endforeach
        </div>
        <div class="fo-kiosk-progress" role="progressbar" aria-label="Tiempo restante antes del cambio" aria-valuemin="0" aria-valuemax="{{ $rotationInterval }}" :aria-valuenow="countdown">
            <span :style="'width: ' + (countdown / @js($rotationInterval) * 100) + '%'"></span>
        </div>
    </div>
    <div class="fo-kiosk-table-wrap">
        <table class="fo-kiosk-table">
            <caption class="sr-only">Audiencias de {{ $currentType }} para hoy</caption>
            <thead><tr><th scope="col">Hora</th><th scope="col">Sala</th><th scope="col">RIT</th><th scope="col">RUC</th><th scope="col">Estado</th></tr></thead>
            <tbody>
            @forelse($audiencias as $audiencia)
                <tr wire:key="audiencia-{{ $audiencia->id }}">
                    <td class="fo-kiosk-emphasis">{{ \Carbon\Carbon::parse($audiencia->hora_inicio)->format('H:i') }}</td>
                    <td class="fo-kiosk-emphasis">{{ $audiencia->sala }} <small>{{ $audiencia->ubicacion }}</small></td>
                    <td>{{ $audiencia->rit }}</td>
                    <td>{{ $audiencia->ruc }}</td>
                    <td><span class="fo-kiosk-status" data-estado="{{ $audiencia->estado }}">@switch($audiencia->estado)
                        @case('POR_REALIZARSE') Por realizarse @break
                        @case('EN_CURSO') En curso @break
                        @case('RECESO') Receso @break
                        @case('FINALIZADA') Finalizada @break
                        @default {{ $audiencia->estado }}
                    @endswitch</span></td>
                </tr>
            @empty
                <tr><td colspan="5" class="fo-kiosk-empty">Sin audiencias de este tipo para hoy.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @auth
        @can('audiencias.estado.cambiar')
            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    // Funcion para verificar y actualizar estados automaticamente
                    const checkAndAutoUpdateAudienciasCortas = () => {
                        const now = new Date();
                        const hours = now.getHours();
                        const minutes = now.getMinutes();
                        const time = `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}`;

                        // Solo actuar si estamos en un rango razonable (ej. entre 12:00 y 16:00)
                        if (hours < 12 || hours > 16) return;

                        // Llamada a Livewire para actualizar estados segun la hora
                        if (time === '13:30') {
                            @this.actualizarEstadoAudienciasCortas('EN_CURSO');
                        } else if (time === '15:00') {
                            @this.actualizarEstadoAudienciasCortas('FINALIZADA');
                        }
                    };

                    // Ejecutar cada minuto (para no perder el minuto exacto)
                    setInterval(checkAndAutoUpdateAudienciasCortas, 60000);
                    // Ejecutar inmediatamente al cargar
                    checkAndAutoUpdateAudienciasCortas();
                });
            </script>
        @endcan
    @endauth
</div>
