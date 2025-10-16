<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Ausentismo;
use Carbon\Carbon;

class AusentismoCalendario extends Component
{
    public int $year;
    public int $month; // 1..12

    /** Fechas (YYYY-MM-DD) de la grilla 7x6 */
    public array $gridDays = [];

    /**
     * 'YYYY-MM-DD' => [
     *   ['id'=>..., 'nombre'=>..., 'cargo'=>..., 'obs'=>..., 'tipo'=>..., 'color'=>..., 'rango'=>...],
     *   ...
     * ]
     */
    public array $eventsByDate = [];

    public ?string $modalDate = null;

    /** Paleta por tipo (normalizada a minúsculas sin tildes) */
    private array $typePalette = [
        'licencia medica'             => 'bg-yellow-300 text-yellow-900',
        'permiso 347'                 => 'bg-red-500 text-white',
        'permiso sin goce de sueldo'  => 'bg-red-500 text-white',
        'feriado legal'               => 'bg-green-300 text-green-900',
        'curso academia'              => 'bg-blue-400 text-white',
        'comision de servicio'        => 'bg-blue-400 text-white',
        'otro'                        => 'bg-gray-300 text-gray-900',
    ];

    /** Devuelve clases Tailwind según tipo, con fallback a 'otro' */
    private function colorForType(?string $tipo): string
    {
        $key = $this->normalize((string)$tipo);
        return $this->typePalette[$key] ?? $this->typePalette['otro'];
    }

    /** Normaliza: minúsculas y sin tildes/acentos para matchear claves */
    private function normalize(string $s): string
    {
        $s = trim(mb_strtolower($s));
        $s = strtr($s, [
            'á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ñ'=>'n',
        ]);
        return $s;
    }

    public function mount(): void
    {
        $today = now();
        $this->year  = (int) $today->year;
        $this->month = (int) $today->month;
        $this->buildCalendar();
    }

    public function previousMonth(): void
    {
        $first = Carbon::create($this->year, $this->month, 1)->subMonth();
        $this->year  = (int) $first->year;
        $this->month = (int) $first->month;
        $this->buildCalendar();
    }

    public function nextMonth(): void
    {
        $first = Carbon::create($this->year, $this->month, 1)->addMonth();
        $this->year  = (int) $first->year;
        $this->month = (int) $first->month;
        $this->buildCalendar();
    }

    private function buildCalendar(): void
    {
        // Rango visible (7x6) desde lunes a domingo
        $firstOfMonth = Carbon::create($this->year, $this->month, 1);
        $startGrid = $firstOfMonth->copy()->startOfMonth()->startOfWeek(Carbon::MONDAY);
        $endGrid   = $firstOfMonth->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);

        // Grilla
        $this->gridDays = [];
        for ($d = $startGrid->copy(); $d->lte($endGrid); $d->addDay()) {
            $this->gridDays[] = $d->format('Y-m-d');
        }

        // Rango del mes
        $monthStart = $firstOfMonth->copy()->startOfMonth();
        $monthEnd   = $firstOfMonth->copy()->endOfMonth();

        // Traer ausencias que intersecten el mes (NO limitar columnas para evitar 1054)
        $absences = Ausentismo::query()
            ->whereDate('fecha_inicio', '<=', $monthEnd)
            ->whereDate('fecha_termino', '>=', $monthStart)
            ->orderBy('fecha_inicio')
            ->get();

        $this->eventsByDate = [];

        foreach ($absences as $a) {
            // Nombre/cargo/obs con fallbacks defensivos
            $nombre = $a->nombre
                ?? $a->funcionario_nombre
                ?? $a->user_name
                ?? $a->name
                ?? 'Sin nombre';

            $cargo = $a->cargo
                ?? $a->puesto
                ?? $a->rol
                ?? $a->position
                ?? null;

            $obs = $a->observacion
                ?? $a->observaciones
                ?? $a->observacion_txt
                ?? $a->obs
                ?? null;

            // Tipo: soporta ambas columnas (históricas y nuevas)
            $tipo  = $a->tipo_ausencia ?? $a->tipo_permiso ?? 'Otro';
            $color = $this->colorForType($tipo);

            // Fechas
            $fi = Carbon::parse($a->fecha_inicio ?? $a->inicio ?? $a->desde);
            $ft = Carbon::parse($a->fecha_termino ?? $a->termino ?? $a->hasta);

            // Recortar al rango visible
            $start = $fi->copy()->max($startGrid);
            $end   = $ft->copy()->min($endGrid);

            for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
                $key = $d->format('Y-m-d');
                $this->eventsByDate[$key] ??= [];
                $this->eventsByDate[$key][] = [
                    'id'     => $a->id,
                    'nombre' => $nombre,
                    'cargo'  => $cargo,
                    'obs'    => $obs,
                    'tipo'   => $tipo,
                    'color'  => $color,
                    'rango'  => sprintf('%s → %s', $fi->format('d-m-Y'), $ft->format('d-m-Y')),
                ];
            }
        }
    }

    public function openDay(string $date): void { $this->modalDate = $date; }
    public function closeModal(): void { $this->modalDate = null; }

    public function render()
    {
        return view('livewire.ausentismo-calendario');
    }
}
