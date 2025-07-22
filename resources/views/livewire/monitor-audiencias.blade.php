<div>
    <input type="date" wire:model="fecha">
    
    @foreach($audiencias as $audiencia)
        <div>
            <h2>{{ $audiencia->rit }}</h2>
            <p>Sala: {{ $audiencia->sala }}</p>
            <p>Hora: {{ $audiencia->hora_inicio }}</p>
            
            <h3>Acusados:</h3>
            <ul>
                @foreach($audiencia->acusados as $acusado)
                    <li>{{ $acusado['nombre_completo'] }} - {{ $acusado['situacion'] }}</li>
                @endforeach
            </ul>
        </div>
    @endforeach
</div>