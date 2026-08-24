<?php

namespace App\Livewire;

use App\Models\Penal;
use App\Models\VisitaCarcelCausa;
use App\Models\VisitaCarcelImputado;
use App\Models\VisitaCarcelImputadoHistorial;
use App\Services\SafeSpreadsheetReader;
use App\Services\VisitaCarcelRevisionPpService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File as Files;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use ZipArchive;

#[Layout('layouts.app')]
class VisitaCarcelPage extends Component
{
    use WithFileUploads;
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public string $ruc = '';

    public string $rit = '';

    public bool $formularioCausaAbierto = false;

    public bool $formularioImputadoAbierto = false;

    public bool $formularioImportacionAbierto = false;

    public bool $informacionImportacionAbierta = false;

    public bool $informacionAlimentacionAbierta = false;

    public ?int $imputadoRevisionPpId = null;

    public string $ultimaRevisionPp = '';

    public ?int $imputadoEditandoId = null;

    public array $imputadoEdicion = [];

    public array $historialEdicion = [];

    public $archivoMovimientos;

    public array $resultadoImportacion = [];

    public array $coincidenciasCruce = [];

    public array $archivosActualizacion = [];

    public array $resultadoActualizacion = [];

    public string $modo = 'comparacion';

    public string $nombre = '';

    public string $rut = '';

    public string $penal = '';

    public string $delito = '';

    public string $fechaIngresoPp = '';

    public string $search = '';

    public string $searchNotificaciones = '';

    public ?int $causaSeleccionadaId = null;

    public function mount(): void
    {
        Gate::authorize('visita_carcel.ver');
    }

    public function render(VisitaCarcelRevisionPpService $revisionPp)
    {
        Gate::authorize('visita_carcel.ver');

        $causasQuery = VisitaCarcelCausa::query()
            ->withCount('imputados')
            ->when($this->search !== '', function ($query) {
                $term = '%'.trim($this->search).'%';

                $query->where(function ($query) use ($term) {
                    $query->where('ruc', 'like', $term)
                        ->orWhere('rit', 'like', $term)
                        ->orWhereHas('imputados', fn ($query) => $query->where('nombre', 'like', $term));
                });
            })
            ->when($this->modo === 'comparacion' && $this->coincidenciasCruce !== [], fn ($query) => $query->whereIn('id', collect($this->coincidenciasCruce)->pluck('causa_id')->unique()))
            ->orderBy('ruc');

        $totalImputados = $this->modo === 'comparacion'
            ? (clone $causasQuery)->get()->sum('imputados_count')
            : 0;
        $causas = $causasQuery->paginate(10);

        $notificaciones = $this->modo === 'notificaciones'
            ? $revisionPp->recordsForDate(now('America/Santiago'))
                ->filter(function (VisitaCarcelImputado $imputado): bool {
                    $term = trim(mb_strtolower($this->searchNotificaciones));

                    return $term === '' || str_contains(mb_strtolower(implode(' ', [$imputado->nombre, $imputado->rut, $imputado->causa->rit, $imputado->causa->ruc])), $term);
                })
                ->values()
            : collect();

        $causaSeleccionada = $this->causaSeleccionadaId
            ? VisitaCarcelCausa::query()->with('imputados.historial')->find($this->causaSeleccionadaId)
            : null;

        $causaSeleccionada?->setRelation('movimientos', collect());

        return view('livewire.visita-carcel-page', compact('causas', 'causaSeleccionada', 'notificaciones', 'totalImputados') + ['penales' => $this->modo === 'alimentacion' ? Penal::query()->where('activo', true)->orderBy('nombre')->pluck('nombre') : collect()]);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function seleccionarModo(string $modo): void
    {
        Gate::authorize('visita_carcel.ver');

        if (! in_array($modo, ['comparacion', 'alimentacion', 'notificaciones'], true)) {
            return;
        }

        $this->modo = $modo;
        $this->salirModoCruce();
        $this->resetPage();
    }

    public function abrirFormularioImportacion(): void
    {
        Gate::authorize('visita_carcel.gestionar');

        $this->resetValidation();
        $this->formularioImportacionAbierto = true;
    }

    public function cerrarFormularioImportacion(): void
    {
        $this->reset(['archivoMovimientos', 'formularioImportacionAbierto']);
        $this->resetValidation();
    }

    public function abrirInformacionImportacion(): void
    {
        Gate::authorize('visita_carcel.gestionar');

        $this->informacionImportacionAbierta = true;
    }

    public function cerrarInformacionImportacion(): void
    {
        $this->informacionImportacionAbierta = false;
    }

    public function abrirInformacionAlimentacion(): void
    {
        Gate::authorize('visita_carcel.gestionar');

        $this->informacionAlimentacionAbierta = true;
    }

    public function cerrarInformacionAlimentacion(): void
    {
        $this->informacionAlimentacionAbierta = false;
    }

    public function importarMovimientos(SafeSpreadsheetReader $reader): void
    {
        Gate::authorize('visita_carcel.gestionar');

        $this->validate([
            'archivoMovimientos' => ['required', File::types(['xlsx', 'xls'])->max(20 * 1024)],
        ]);

        try {
            $spreadsheet = $reader->load($this->archivoMovimientos->getRealPath(), allowXls: true, maxRows: 5000, maxColumns: 20);
            $rows = $spreadsheet->getActiveSheet()->toArray(null, true, false, true);
            $headers = $this->headers(array_shift($rows) ?: []);

            if (array_diff(['rit', 'anocausa', 'ruc'], $headers) !== []) {
                $this->addError('archivoMovimientos', 'La primera fila debe incluir las columnas RIT, AÑOCAUSA y RUC.');

                return;
            }

            $result = ['filas_procesadas' => 0, 'movimientos_creados' => 0, 'duplicados' => 0, 'causas_no_encontradas' => 0, 'imputados_no_encontrados' => 0, 'coincidencias_ambiguas' => 0, 'inconsistencias' => 0, 'errores' => []];
            $matches = [];

            foreach ($rows as $index => $row) {
                $data = $this->rowData($headers, $row);
                if ($this->rowIsEmpty($data)) {
                    continue;
                }

                $result['filas_procesadas']++;
                $fila = $index + 2;
                $ruc = trim((string) $data['ruc']);
                $rit = trim((string) $data['rit']).'-'.trim((string) $data['anocausa']);

                if ($ruc === '' || $rit === '-') {
                    $result['inconsistencias']++;
                    $this->agregarErrorImportacion($result, $fila, 'faltan RUC, RIT o AÑOCAUSA.');

                    continue;
                }

                $causa = VisitaCarcelCausa::query()->where('ruc', $ruc)->first();
                if (! $causa) {
                    $result['causas_no_encontradas']++;
                    $this->agregarErrorImportacion($result, $fila, "no existe una causa con RUC {$ruc}.");

                    continue;
                }

                if ($causa->rit !== $rit) {
                    $result['inconsistencias']++;
                    $this->agregarErrorImportacion($result, $fila, "el RIT {$rit} no coincide con el catálogo.");

                    continue;
                }

                $payload = ['ruc' => $ruc, 'rit' => $rit, 'anio' => trim((string) $data['anocausa']), 'causa_id' => $causa->id];
                $fingerprint = hash('sha256', implode('|', $payload));
                if (isset($matches[$fingerprint])) {
                    $result['duplicados']++;
                    $this->agregarErrorImportacion($result, $fila, 'registro duplicado en esta planilla.');

                    continue;
                }

                $matches[$fingerprint] = $payload;
                $result['movimientos_creados']++;
            }

            $this->resultadoImportacion = $result;
            $this->coincidenciasCruce = array_values($matches);
            session()->put('visita_carcel.comparacion', $this->coincidenciasCruce);
            $this->reset('archivoMovimientos');
            session()->flash('ok', 'Comparacion completada. Los resultados son temporales y no se guardaron en el catalogo.');
        } catch (\RuntimeException $exception) {
            $this->addError('archivoMovimientos', $exception->getMessage());
        } catch (\Throwable $exception) {
            report($exception);
            $this->addError('archivoMovimientos', 'No fue posible procesar la planilla. Verifique que el archivo no esté dañado y vuelva a intentarlo.');
        }
    }

    public function salirModoCruce(): void
    {
        $this->reset(['coincidenciasCruce', 'resultadoImportacion']);
        session()->forget('visita_carcel.comparacion');
    }

    public function actualizarDesdeArchivos(SafeSpreadsheetReader $reader): void
    {
        Gate::authorize('visita_carcel.gestionar');
        $this->validate([
            'archivosActualizacion' => ['required', 'array', 'max:25'],
            'archivosActualizacion.*' => ['required', File::types(['xlsx', 'xls', 'zip'])->max(20 * 1024)],
        ]);

        try {
            $result = ['archivos_procesados' => 0, 'archivos_omitidos' => 0, 'procesadas' => 0, 'causas_creadas' => 0, 'imputados_creados' => 0, 'actualizadas' => 0, 'sin_coincidencia' => 0, 'sin_cambios' => 0, 'archivos' => []];

            foreach ($this->archivosActualizacion as $archivo) {
                $extension = strtolower($archivo->getClientOriginalExtension());

                if ($extension === 'zip') {
                    foreach ($this->procesarZipActualizacion($reader, $archivo->getRealPath(), $archivo->getClientOriginalName()) as $detalle) {
                        $this->acumularResultadoActualizacion($result, $detalle);
                    }

                    continue;
                }

                $this->acumularResultadoActualizacion($result, $this->procesarArchivoActualizacion($reader, $archivo->getRealPath(), $archivo->getClientOriginalName()));
            }

            $this->resultadoActualizacion = $result;
            $this->reset('archivosActualizacion');
            session()->flash('ok', "Actualizacion completada: {$result['actualizadas']} actualizados y {$result['imputados_creados']} imputados creados.");
        } catch (\Throwable $exception) {
            report($exception);
            $this->addError('archivosActualizacion', 'No fue posible procesar los archivos. Revise el formato e intente nuevamente.');
        }
    }

    private function procesarArchivoActualizacion(SafeSpreadsheetReader $reader, string $path, string $archivo): array
    {
        $result = ['archivo' => $archivo, 'procesadas' => 0, 'causas_creadas' => 0, 'imputados_creados' => 0, 'actualizadas' => 0, 'sin_coincidencia' => 0, 'sin_cambios' => 0, 'error' => null];

        try {
            $spreadsheet = $reader->loadAll($path, true, 5000, 70);
        } catch (\Throwable $exception) {
            $result['error'] = $exception->getMessage();

            return $result;
        }

        $errores = [];

        foreach ($spreadsheet->getWorksheetIterator() as $hoja) {
            $detalle = $this->procesarHojaActualizacion($hoja->toArray(null, true, false, true), $hoja->getTitle(), $archivo);

            if ($detalle['error'] !== null) {
                $errores[] = $hoja->getTitle().': '.$detalle['error'];

                continue;
            }

            foreach (['procesadas', 'causas_creadas', 'imputados_creados', 'actualizadas', 'sin_coincidencia', 'sin_cambios'] as $campo) {
                $result[$campo] += $detalle[$campo];
            }
        }

        if ($result['procesadas'] === 0 && $errores !== []) {
            $result['error'] = implode(' ', $errores);
        }

        return $result;
    }

    private function procesarHojaActualizacion(array $rows, string $nombreHoja, string $archivo): array
    {
        $result = ['procesadas' => 0, 'causas_creadas' => 0, 'imputados_creados' => 0, 'actualizadas' => 0, 'sin_coincidencia' => 0, 'sin_cambios' => 0, 'error' => null];
        $encabezados = $this->encabezadosActualizacion($rows);

        if ($encabezados === null) {
            $result['error'] = 'No se encontraron las columnas estandarizadas de exportacion.';

            return $result;
        }

        [$headers, $filaEncabezados] = $encabezados;
        $penal = $this->penalPlanilla($rows, $filaEncabezados, $nombreHoja);

        foreach (array_slice($rows, $filaEncabezados + 1) as $row) {
            $data = $this->rowData($headers, $row);
            if ($this->rowIsEmpty($data)) {
                continue;
            }

            $result['procesadas']++;
            $rit = trim((string) $data['rit']).'-'.trim((string) $data['ano']);
            $ruc = trim((string) $data['ruc']);
            $causa = VisitaCarcelCausa::query()->where('ruc', $ruc)->first();

            if ($causa && $causa->rit !== $rit) {
                $result['sin_coincidencia']++;

                continue;
            }

            $imputado = $causa
                ? $causa->imputados()->get()->first(fn (VisitaCarcelImputado $imputado) => $this->normalizarRut($imputado->rut) === $this->normalizarRut((string) $data['rut']))
                : null;

            if (! $imputado) {
                $nuevoImputado = [
                    'nombre' => trim((string) ($data['imputado'] ?? '')),
                    'rut' => trim((string) $data['rut']),
                    'penal' => $penal,
                    'delito' => trim((string) ($data['delito'] ?? '')),
                    'fecha_ingreso_pp' => $this->parseExcelDate($data['fecingresopp'] ?? null),
                ];

                if (in_array(null, $nuevoImputado, true) || in_array('', $nuevoImputado, true)) {
                    $result['sin_coincidencia']++;

                    continue;
                }

                DB::transaction(function () use (&$causa, &$imputado, $ruc, $rit, $nuevoImputado, $data): void {
                    $causa ??= VisitaCarcelCausa::query()->create([
                        'ruc' => $ruc,
                        'rit' => $rit,
                        'created_by' => Auth::id(),
                        'updated_by' => Auth::id(),
                    ]);
                    $imputado = $causa->imputados()->create($nuevoImputado + [
                        'plazo_cierre' => $this->parseExcelDate($data['plazocierre']),
                        'ultima_rev_pp' => $this->parseExcelDate($data['ultimarevpp']),
                        'juez' => trim((string) $data['juez']) ?: null,
                        'defensor' => trim((string) $data['defensor']) ?: null,
                        'fecha_ult_res' => $this->parseExcelDate($data['fecultres']),
                        'ult_resolucion' => trim((string) $data['ultresolucion']) ?: null,
                        'created_by' => Auth::id(),
                        'updated_by' => Auth::id(),
                    ]);
                });

                if ($causa->wasRecentlyCreated) {
                    $result['causas_creadas']++;
                }
                $result['imputados_creados']++;

                continue;
            }

            $payload = [
                'plazo_cierre' => $this->parseExcelDate($data['plazocierre']),
                'ultima_rev_pp' => $this->parseExcelDate($data['ultimarevpp']),
                'juez' => trim((string) $data['juez']) ?: null,
                'defensor' => trim((string) $data['defensor']) ?: null,
                'fecha_ult_res' => $this->parseExcelDate($data['fecultres']),
                'ult_resolucion' => trim((string) $data['ultresolucion']) ?: null,
            ];
            $changes = [];
            foreach ($payload as $field => $value) {
                $old = $imputado->getRawOriginal($field);
                if ((string) $old !== (string) $value) {
                    $changes[$field] = ['old' => $old, 'new' => $value];
                }
            }

            if ($changes === []) {
                $result['sin_cambios']++;

                continue;
            }

            DB::transaction(function () use ($imputado, $payload, $changes, $archivo): void {
                $imputado->update($payload + ['updated_by' => Auth::id()]);
                VisitaCarcelImputadoHistorial::query()->create(['visita_carcel_imputado_id' => $imputado->id, 'user_id' => Auth::id(), 'archivo' => $archivo, 'cambios' => $changes]);
            });
            $result['actualizadas']++;
        }

        return $result;
    }

    public function abrirFormularioCausa(): void
    {
        Gate::authorize('visita_carcel.gestionar');

        $this->resetValidation();
        $this->formularioCausaAbierto = true;
    }

    public function cerrarFormularioCausa(): void
    {
        $this->reset(['ruc', 'rit', 'formularioCausaAbierto']);
        $this->resetValidation();
    }

    public function guardarCausa(): void
    {
        Gate::authorize('visita_carcel.gestionar');

        $validated = $this->validate([
            'ruc' => ['required', 'string', 'max:30', Rule::unique('visita_carcel_causas', 'ruc')->whereNull('deleted_at')],
            'rit' => ['required', 'string', 'max:50'],
        ]);

        VisitaCarcelCausa::query()->create([
            'ruc' => trim($validated['ruc']),
            'rit' => trim($validated['rit']),
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);

        $this->cerrarFormularioCausa();
        $this->dispatch('app-toast', message: 'Causa registrada correctamente.');
    }

    public function verFicha(int $causaId): void
    {
        Gate::authorize('visita_carcel.ver');

        $this->causaSeleccionadaId = $causaId;
    }

    public function cerrarFicha(): void
    {
        $this->causaSeleccionadaId = null;
        $this->cerrarFormularioImputado();
        $this->cerrarFormularioRevisionPp();
    }

    public function abrirFormularioImputado(): void
    {
        Gate::authorize('visita_carcel.gestionar');

        $this->resetValidation();
        $this->formularioImputadoAbierto = true;
    }

    public function cerrarFormularioImputado(): void
    {
        $this->reset(['nombre', 'rut', 'penal', 'delito', 'fechaIngresoPp', 'formularioImputadoAbierto']);
        $this->resetValidation();
    }

    public function guardarImputado(): void
    {
        Gate::authorize('visita_carcel.gestionar');

        $validated = $this->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'rut' => ['required', 'string', 'max:20'],
            'penal' => ['required', 'string', 'max:255'],
            'delito' => ['required', 'string'],
            'fechaIngresoPp' => ['required', 'date'],
        ]);

        $causa = VisitaCarcelCausa::query()->findOrFail($this->causaSeleccionadaId);
        $causa->imputados()->create([
            'nombre' => trim($validated['nombre']),
            'rut' => trim($validated['rut']),
            'penal' => trim($validated['penal']),
            'delito' => trim($validated['delito']),
            'fecha_ingreso_pp' => $validated['fechaIngresoPp'],
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);

        $this->cerrarFormularioImputado();
        $this->dispatch('app-toast', message: 'Imputado registrado correctamente.');
    }

    public function eliminarImputado(int $imputadoId): void
    {
        Gate::authorize('visita_carcel.gestionar');

        $imputado = VisitaCarcelImputado::query()->findOrFail($imputadoId);
        $imputado->update(['deleted_by' => Auth::id()]);
        $imputado->delete();

        $this->dispatch('app-toast', message: 'Imputado eliminado del catálogo.');
    }

    public function abrirEdicionImputado(int $imputadoId): void
    {
        Gate::authorize('visita_carcel.gestionar');

        $imputado = VisitaCarcelImputado::query()->with('historial')->findOrFail($imputadoId);
        $this->imputadoEditandoId = $imputado->id;
        $this->imputadoEdicion = [
            'nombre' => $imputado->nombre,
            'rut' => $imputado->rut,
            'penal' => $imputado->penal,
            'delito' => $imputado->delito,
            'fecha_ingreso_pp' => $imputado->fecha_ingreso_pp->toDateString(),
            'plazo_cierre' => $imputado->plazo_cierre?->toDateString(),
            'ultima_rev_pp' => $imputado->ultima_rev_pp?->toDateString(),
            'juez' => $imputado->juez,
            'defensor' => $imputado->defensor,
            'fecha_ult_res' => $imputado->fecha_ult_res?->toDateString(),
            'ult_resolucion' => $imputado->ult_resolucion,
        ];
        $this->historialEdicion = $imputado->historial->take(5)->map(fn (VisitaCarcelImputadoHistorial $entry): array => [
            'fecha' => $entry->created_at->format('d/m/Y H:i'),
            'usuario_id' => $entry->user_id,
            'cambios' => $entry->cambios,
        ])->all();
        $this->resetValidation();
    }

    public function cerrarEdicionImputado(): void
    {
        $this->reset(['imputadoEditandoId', 'imputadoEdicion', 'historialEdicion']);
        $this->resetValidation();
    }

    public function guardarEdicionImputado(): void
    {
        Gate::authorize('visita_carcel.gestionar');

        $validated = $this->validate([
            'imputadoEdicion.nombre' => ['required', 'string', 'max:255'],
            'imputadoEdicion.rut' => ['required', 'string', 'max:20'],
            'imputadoEdicion.penal' => ['required', 'string', 'max:255'],
            'imputadoEdicion.delito' => ['required', 'string'],
            'imputadoEdicion.fecha_ingreso_pp' => ['required', 'date'],
            'imputadoEdicion.plazo_cierre' => ['nullable', 'date'],
            'imputadoEdicion.ultima_rev_pp' => ['nullable', 'date'],
            'imputadoEdicion.juez' => ['nullable', 'string', 'max:255'],
            'imputadoEdicion.defensor' => ['nullable', 'string', 'max:255'],
            'imputadoEdicion.fecha_ult_res' => ['nullable', 'date'],
            'imputadoEdicion.ult_resolucion' => ['nullable', 'string'],
        ]);

        $imputado = VisitaCarcelImputado::query()->findOrFail($this->imputadoEditandoId);
        $payload = $validated['imputadoEdicion'];
        $changes = [];

        foreach ($payload as $field => $value) {
            $old = $imputado->getRawOriginal($field);
            if ((string) $old !== (string) $value) {
                $changes[$field] = ['old' => $old, 'new' => $value];
            }
        }

        if ($changes !== []) {
            DB::transaction(function () use ($imputado, $payload, $changes): void {
                $imputado->update($payload + ['updated_by' => Auth::id()]);
                VisitaCarcelImputadoHistorial::query()->create([
                    'visita_carcel_imputado_id' => $imputado->id,
                    'user_id' => Auth::id(),
                    'archivo' => 'Edición manual desde ficha de causa',
                    'cambios' => $changes,
                ]);
            });
        }

        $this->cerrarEdicionImputado();
        $this->dispatch('app-toast', message: $changes === [] ? 'No se detectaron cambios.' : 'Imputado actualizado correctamente.');
    }

    public function abrirFormularioRevisionPp(int $imputadoId): void
    {
        Gate::authorize('visita_carcel.gestionar');

        $imputado = VisitaCarcelImputado::query()->findOrFail($imputadoId);
        $this->imputadoRevisionPpId = $imputado->id;
        $this->ultimaRevisionPp = $imputado->ultima_rev_pp?->toDateString() ?? '';
        $this->resetValidation();
    }

    public function cerrarFormularioRevisionPp(): void
    {
        $this->reset(['imputadoRevisionPpId', 'ultimaRevisionPp']);
        $this->resetValidation();
    }

    public function usarFechaActualRevisionPp(): void
    {
        $this->ultimaRevisionPp = now('America/Santiago')->toDateString();
    }

    public function actualizarRevisionPp(): void
    {
        Gate::authorize('visita_carcel.gestionar');

        $validated = $this->validate(['ultimaRevisionPp' => ['required', 'date']]);
        $imputado = VisitaCarcelImputado::query()->findOrFail($this->imputadoRevisionPpId);
        $fechaAnterior = $imputado->ultima_rev_pp?->toDateString();

        if ($fechaAnterior !== $validated['ultimaRevisionPp']) {
            DB::transaction(function () use ($imputado, $fechaAnterior, $validated): void {
                $imputado->update(['ultima_rev_pp' => $validated['ultimaRevisionPp'], 'updated_by' => Auth::id()]);
                VisitaCarcelImputadoHistorial::query()->create([
                    'visita_carcel_imputado_id' => $imputado->id,
                    'user_id' => Auth::id(),
                    'archivo' => 'Actualización manual de última revisión PP',
                    'cambios' => ['ultima_rev_pp' => ['old' => $fechaAnterior, 'new' => $validated['ultimaRevisionPp']]],
                ]);
            });
        }

        $this->cerrarFormularioRevisionPp();
        $this->dispatch('app-toast', message: 'Última revisión de PP actualizada correctamente.');
    }

    public function eliminarCausa(int $causaId): void
    {
        Gate::authorize('visita_carcel.gestionar');

        DB::transaction(function () use ($causaId) {
            $causa = VisitaCarcelCausa::query()->with('imputados')->findOrFail($causaId);

            foreach ($causa->imputados as $imputado) {
                $imputado->update(['deleted_by' => Auth::id()]);
                $imputado->delete();
            }

            $causa->update(['deleted_by' => Auth::id()]);
            $causa->delete();
        });

        if ($this->causaSeleccionadaId === $causaId) {
            $this->cerrarFicha();
        }

        $this->dispatch('app-toast', message: 'Causa eliminada del catálogo.');
    }

    private function procesarZipActualizacion(SafeSpreadsheetReader $reader, string $path, string $archivo): array
    {
        $zip = new ZipArchive;

        if ($zip->open($path) !== true) {
            return [$this->resultadoArchivoInvalido($archivo, 'No fue posible abrir el archivo ZIP.')];
        }

        $archivos = [];
        $totalDescomprimido = 0;

        for ($indice = 0; $indice < $zip->numFiles; $indice++) {
            $entrada = $zip->statIndex($indice);
            $nombre = $entrada['name'] ?? '';

            if ($nombre === '' || str_ends_with($nombre, '/') || str_starts_with(basename($nombre), '.~lock')) {
                continue;
            }

            $extension = strtolower(pathinfo($nombre, PATHINFO_EXTENSION));
            if (! in_array($extension, ['xlsx', 'xls'], true)) {
                continue;
            }

            $tamano = (int) ($entrada['size'] ?? 0);
            if (str_contains($nombre, '..') || str_starts_with($nombre, '/') || $tamano > 20 * 1024 * 1024) {
                $zip->close();

                return [$this->resultadoArchivoInvalido($archivo, 'El ZIP contiene una planilla no permitida.')];
            }

            $totalDescomprimido += $tamano;
            $archivos[] = ['indice' => $indice, 'nombre' => basename($nombre), 'extension' => $extension];
        }

        if ($archivos === []) {
            $zip->close();

            return [$this->resultadoArchivoInvalido($archivo, 'El ZIP no contiene planillas XLS o XLSX.')];
        }

        if (count($archivos) > 25 || $totalDescomprimido > 100 * 1024 * 1024) {
            $zip->close();

            return [$this->resultadoArchivoInvalido($archivo, 'El ZIP excede el limite de 25 planillas o 100 MB descomprimidos.')];
        }

        $directorio = sys_get_temp_dir().'/visita-carcel-'.uniqid();
        Files::ensureDirectoryExists($directorio);
        $resultados = [];

        try {
            foreach ($archivos as $posicion => $entrada) {
                $destino = $directorio.'/'.sprintf('%03d', $posicion).'.'.$entrada['extension'];
                $origen = $zip->getStream($zip->getNameIndex($entrada['indice']));
                $salida = fopen($destino, 'wb');

                if ($origen === false || $salida === false) {
                    if (is_resource($origen)) {
                        fclose($origen);
                    }
                    if (is_resource($salida)) {
                        fclose($salida);
                    }

                    $resultados[] = $this->resultadoArchivoInvalido($entrada['nombre'], 'No fue posible extraer la planilla del ZIP.');

                    continue;
                }

                stream_copy_to_stream($origen, $salida);
                fclose($origen);
                fclose($salida);
                $resultados[] = $this->procesarArchivoActualizacion($reader, $destino, $entrada['nombre']);
            }
        } finally {
            $zip->close();
            Files::deleteDirectory($directorio);
        }

        return $resultados;
    }

    private function encabezadosActualizacion(array $rows): ?array
    {
        $required = ['rit', 'ano', 'ruc', 'rut', 'plazocierre', 'ultimarevpp', 'juez', 'defensor', 'fecultres', 'ultresolucion'];

        foreach (array_slice($rows, 0, 10) as $indice => $fila) {
            $headers = $this->headers($fila);

            if (array_diff($required, $headers) === []) {
                return [$headers, $indice];
            }
        }

        return null;
    }

    private function penalPlanilla(array $rows, int $filaEncabezados, string $nombreHoja = ''): ?string
    {
        $referencia = $this->normalizarPenal($nombreHoja.' '.collect(array_slice($rows, 0, $filaEncabezados))->flatten()->implode(' '));
        $penales = Penal::query()->where('activo', true)->get();

        foreach ([
            'CDPSANTIAGOUNO' => 'CDPSTGOUNO',
            'CCPDECOLINAII' => 'CCPCOLINAII',
            'CDPDESANTIAGOSUR' => 'CDPSTGOSUR',
            'CUARTELGENERALPDIBORGONO' => 'CUARTELGRALPDIBORGONO',
        ] as $origen => $destino) {
            if (str_contains($referencia, $origen)) {
                return $penales->first(fn (Penal $penal) => $this->normalizarPenal($penal->nombre) === $destino)?->nombre;
            }
        }

        return $penales
            ->first(fn (Penal $penal) => str_contains($referencia, $this->normalizarPenal($penal->nombre)))
            ?->nombre;
    }

    private function acumularResultadoActualizacion(array &$resultado, array $detalle): void
    {
        $resultado['archivos'][] = $detalle;
        $resultado[$detalle['error'] === null ? 'archivos_procesados' : 'archivos_omitidos']++;

        foreach (['procesadas', 'causas_creadas', 'imputados_creados', 'actualizadas', 'sin_coincidencia', 'sin_cambios'] as $campo) {
            $resultado[$campo] += $detalle[$campo];
        }
    }

    private function resultadoArchivoInvalido(string $archivo, string $error): array
    {
        return ['archivo' => $archivo, 'procesadas' => 0, 'causas_creadas' => 0, 'imputados_creados' => 0, 'actualizadas' => 0, 'sin_coincidencia' => 0, 'sin_cambios' => 0, 'error' => $error];
    }

    private function headers(array $headers): array
    {
        return array_map(fn ($header) => $this->normalizeHeader((string) $header), $headers);
    }

    private function rowData(array $headers, array $row): array
    {
        $data = [];

        foreach ($headers as $column => $header) {
            $data[$header] = $row[$column] ?? null;
        }

        return $data;
    }

    private function rowIsEmpty(array $data): bool
    {
        return collect($data)->every(fn ($value) => trim((string) $value) === '');
    }

    private function agregarErrorImportacion(array &$result, int $fila, string $motivo): void
    {
        if (count($result['errores']) < 20) {
            $result['errores'][] = "Fila {$fila}: {$motivo}";
        }
    }

    private function normalizeHeader(string $value): string
    {
        return str_replace([' ', '_', '.'], '', strtr(mb_strtolower(trim($value)), ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n']));
    }

    private function parseExcelDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return Date::excelToDateTimeObject((float) $value)->format('Y-m-d');
        }

        $date = preg_replace("/[\\s'’‘]+/u", '', trim((string) $value)) ?? '';

        foreach (['d/m/Y H:i', 'd/m/Y H:i:s', 'd/m/Y', 'Y-m-d H:i:s', 'Y-m-d'] as $format) {
            try {
                return Carbon::createFromFormat($format, $date, 'America/Santiago')->toDateString();
            } catch (\Throwable) {
                continue;
            }
        }

        return null;
    }

    private function imputadoParaMovimiento(VisitaCarcelCausa $causa, string $rut, string $nombre): VisitaCarcelImputado|false|null
    {
        $imputados = $causa->imputados()->get();
        $rutNormalizado = $this->normalizarRut($rut);

        if ($rutNormalizado !== '') {
            $coincidencias = $imputados->filter(fn (VisitaCarcelImputado $imputado) => $this->normalizarRut($imputado->rut) === $rutNormalizado);

            if ($coincidencias->count() === 1) {
                return $coincidencias->first();
            }
        }

        $nombreNormalizado = $this->normalizarTexto($nombre);

        if ($nombreNormalizado === '') {
            return null;
        }

        $coincidencias = $imputados->filter(fn (VisitaCarcelImputado $imputado) => $this->normalizarTexto($imputado->nombre) === $nombreNormalizado);

        return match ($coincidencias->count()) {
            0 => null,
            1 => $coincidencias->first(),
            default => false,
        };
    }

    private function normalizarRut(string $rut): string
    {
        return strtoupper(preg_replace('/[^0-9K]/i', '', $rut) ?? '');
    }

    private function normalizarPenal(string $penal): string
    {
        return preg_replace('/[^A-Z0-9]/', '', $this->normalizarTexto($penal)) ?? '';
    }

    private function normalizarTexto(string $texto): string
    {
        return preg_replace('/\s+/', ' ', strtr(mb_strtoupper(trim($texto)), ['Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ñ' => 'N'])) ?? '';
    }
}
