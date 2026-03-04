<div>
    <!-- Barra de búsqueda y controles -->
    <div class="d-flex justify-content-between mb-3 align-items-center gap-2">
        <div class="input-group w-50">
            <input type="text"
                   class="form-control rounded"
                   placeholder="Buscar sentencias..."
                   wire:model.live.debounce.200ms="search">

            <span class="input-group-text">
                <div wire:loading wire:target="search">
                    <span class="spinner-border spinner-border-sm"></span>
                </div>
            </span>
        </div>

        <div class="gap-2">
            <select wire:model.live.debounce.50ms="filtroInstancia" class="form-control">
                <option value="">-- Todas las instancias --</option>
                @foreach($instancias as $inst)
                    <option value="{{ $inst }}">{{ $inst }}</option>
                @endforeach
            </select>
        </div>

        <div class="d-flex gap-2">
            <select class="form-select w-auto rounded" wire:model.live="perPage">
                <option value="10">10 por página</option>
                <option value="25">25 por página</option>
                <option value="50">50 por página</option>
                <option value="100">100 por página</option>
            </select>
        </div>
    </div>

    <!-- Tabla -->
    <div class="table-responsive rounded border">
        <table class="table table-striped table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th style="cursor:pointer" wire:click="sortBy('ruc')">RUC</th>
                    <th style="cursor:pointer" wire:click="sortBy('rit')">RIT</th>
                    <th style="cursor:pointer" wire:click="sortBy('ano')">AÑO</th>
                    <th style="cursor:pointer" wire:click="sortBy('nombre_partes')">NOMBRE DE LAS PARTES</th>
                    <th style="cursor:pointer" wire:click="sortBy('materia')">MATERIA</th>
                    <th style="cursor:pointer" wire:click="sortBy('fecha_decision')">FECHA DECISIÓN</th>
                    <th style="cursor:pointer" wire:click="sortBy('glosa_decision')">GLOSA DECISIÓN</th>
                    <th style="cursor:pointer" wire:click="sortBy('juez')">JUEZ</th>
                    <th style="cursor:pointer" wire:click="sortBy('instancia')">INSTANCIA</th>
                </tr>
            </thead>

            <tbody>
                @forelse($sentencias as $s)
                    <tr>
                        <td>{{ $s->ruc }}</td>
                        <td>{{ $s->rit }}</td>
                        <td>{{ $s->ano }}</td>
                        <td class="text-truncate" style="max-width: 340px;">
                            {{ $s->nombre_partes }}
                        </td>
                        <td>{{ $s->materia }}</td>
                        <td>{{ optional($s->fecha_decision)->format('d-m-Y') }}</td>
                        <td class="text-truncate" style="max-width: 280px;">
                            {{ $s->glosa_decision }}
                        </td>
                        <td>{{ $s->juez }}</td>
                        <td>{{ $s->instancia }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center py-4">
                            No se encontraron sentencias
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Paginación -->
    <div class="d-flex justify-content-end align-items-center mt-3">
        {{ $sentencias->links() }}
    </div>
</div>