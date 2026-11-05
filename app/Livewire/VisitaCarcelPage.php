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
use Livewire\Attributes\Locked;
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

    public bool $gestionPenalesAbierta = false;

    public string $searchPenales = '';

    public string $nuevoPenal = '';

    public bool $nuevoPenalEnSantiago = false;

    #[Locked]
    public ?int $imputadoRevisionPpId = null;

    public string $ultimaRevisionPp = '';

    #[Locked]
    public ?int $imputadoEditandoId = null;

    public array $imputadoEdicion = [];

    #[Locked]
    public array $historialEdicion = [];

    public $archivoMovimientos;

    #[Locked]
    public array $resultadoImportacion = [];

    #[Locked]
    public array $coincidenciasCruce = [];

    #[Locked]
    public array $causasAusentesIds = [];

    #[Locked]
    public bool $comparacionAusenciasDisponible = false;

    #[Locked]
    public string $avisoComparacionAusencias = '';

    public bool $revisionCausasAusentesAbierta = false;

    public string $tipoImportacion = 'simple';

    public bool $revisionCausasPendientesAbierta = false;

    public bool $revisionImputadosPendientesAbierta = false;

    #[Locked]
    public array $causasPendientes = [];

    public array $causasPendientesSeleccionadas = [];

    #[Locked]
    public array $imputadosPendientes = [];

    public array $imputadosPendientesSeleccionados = [];

    public array $archivosActualizacion = [];

    #[Locked]
    public array $resultadoActualizacion = [];

    #[Locked]
    public string $modo = 'comparacion';

    public string $nombre = '';

    public string $rut = '';

    public string $penal = '';

    public string $delito = '';

    public string $fechaIngresoPp = '';

    public string $search = '';

    public string $searchNotificaciones = '';

    public string $reporteDesde = '';

    public string $reporteHasta = '';

    #[Locked]
    public ?int $causaSeleccionadaId = null;

    public function mount(): void
    {
        Gate::authorize('visita_carcel.ver');
        $this->reporteDesde = now('America/Santiago')->startOfMonth()->toDateString();
        $this->reporteHasta = now('America/Santiago')->toDateString();
    }

    public function render(VisitaCarcelRevisionPpService $revisionPp)
    {
        Gate::authorize('visita_carcel.ver');

        if ($this->modo === 'reportes') {
            Gate::authorize('visita_carcel.reportes');
        }

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
        $causasAusentes = VisitaCarcelCausa::query()
            ->whereIn('id', $this->causasAusentesIds)
            ->with('imputados')
            ->orderBy('ruc')
            ->paginate(10, ['*'], 'ausentesPage');

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

        $penales = Penal::query()->where('activo', true)->orderBy('nombre')->get();
        $totalPenales = $penales->count();
        $penalesGestion = $this->modo === 'alimentacion'
            ? $penales->when($this->searchPenales !== '', fn ($items) => $items->filter(fn (Penal $penal) => str_contains(mb_strtolower($penal->nombre), mb_strtolower(trim($this->searchPenales))))->values())
            : collect();

        $reporte = $this->modo === 'reportes'
            ? $this->reporteControl()
            : ['agregados' => collect(), 'eliminados' => collect(), 'modificados' => collect(), 'por_penal' => collect(), 'total_imputados' => 0];

        return view('livewire.visita-carcel-page', compact('causas', 'causasAusentes', 'causaSeleccionada', 'notificaciones', 'totalImputados', 'penales', 'penalesGestion', 'totalPenales', 'reporte'));
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function seleccionarModo(string $modo): void
    {
        Gate::authorize('visita_carcel.ver');

        if (! in_array($modo, ['comparacion', 'alimentacion', 'notificaciones', 'reportes'], true)) {
            return;
        }

        if ($modo === 'reportes') {
            Gate::authorize('visita_carcel.reportes');
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

    public function abrirGestionPenales(): void
    {
        Gate::authorize('visita_carcel.ver');

        $this->resetValidation();
        $this->gestionPenalesAbierta = true;
    }

    public function cerrarGestionPenales(): void
    {
        $this->reset(['gestionPenalesAbierta', 'searchPenales', 'nuevoPenal', 'nuevoPenalEnSantiago']);
        $this->resetValidation();
    }

    public function guardarPenal(): void
    {
        Gate::authorize('visita_carcel.gestionar');

        $this->validate(['nuevoPenal' => ['required', 'string', 'max:255'], 'nuevoPenalEnSantiago' => ['boolean']]);
        $nombre = trim($this->nuevoPenal);
        $existente = Penal::withTrashed()->get()->first(fn (Penal $penal) => $this->normalizarPenal($penal->nombre) === $this->normalizarPenal($nombre));

        if ($existente?->trashed()) {
            $existente->restore();
            $existente->update(['activo' => true, 'exportar_en_santiago' => $this->nuevoPenalEnSantiago]);
            $mensaje = "Penal {$existente->nombre} restaurado.";
        } elseif ($existente) {
            $this->addError('nuevoPenal', 'Este penal ya está activo.');

            return;
        } else {
            Penal::query()->create(['nombre' => $nombre, 'activo' => true, 'exportar_en_santiago' => $this->nuevoPenalEnSantiago]);
            $mensaje = "Penal {$nombre} agregado.";
        }

        $this->reset(['nuevoPenal', 'nuevoPenalEnSantiago']);
        $this->resetValidation(['nuevoPenal', 'nuevoPenalEnSantiago']);
        session()->flash('ok', $mensaje);
    }

    public function eliminarPenal(int $penalId): void
    {
        Gate::authorize('visita_carcel.gestionar');

        Penal::query()->findOrFail($penalId)->delete();
        session()->flash('ok', 'Penal eliminado de los activos.');
    }

    public function alternarExportacionSantiago(int $penalId): void
    {
        Gate::authorize('visita_carcel.gestionar');

        $penal = Penal::query()->findOrFail($penalId);
        $penal->update(['exportar_en_santiago' => ! $penal->exportar_en_santiago]);
        $this->dispatch('app-toast', message: $penal->exportar_en_santiago ? 'Penal incluido en el libro Santiago.' : 'Penal retirado del libro Santiago.');
    }

    public function importarMovimientos(SafeSpreadsheetReader $reader): void
    {
        Gate::authorize('visita_carcel.gestionar');

        $this->reset(['causasAusentesIds', 'comparacionAusenciasDisponible', 'avisoComparacionAusencias', 'revisionCausasAusentesAbierta']);
        $this->resetPage('ausentesPage');

        $this->validate([
            'archivoMovimientos' => ['required', File::types(['xlsx', 'xls'])->max(20 * 1024)],
            'tipoImportacion' => ['required', 'in:simple,libro'],
        ]);

        if ($this->tipoImportacion === 'libro') {
            $this->importarLibroVisitaCarcel($reader);

            return;
        }

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
            $rucsPresentes = [];
            $lecturaIncompleta = false;

            foreach ($rows as $index => $row) {
                $data = $this->rowData($headers, $row);
                if ($this->rowIsEmpty($data)) {
                    continue;
                }

                $result['filas_procesadas']++;
                $fila = $index + 2;
                $ruc = trim((string) $data['ruc']);
                if ($ruc !== '') {
                    $rucsPresentes[$ruc] = true;
                } else {
                    $lecturaIncompleta = true;
                }
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
            $this->compararCausasAusentes($rucsPresentes, $lecturaIncompleta);
            $this->coincidenciasCruce = array_values($matches);
            $this->causasPendientes = [];
            $this->causasPendientesSeleccionadas = [];
            $this->imputadosPendientes = [];
            $this->imputadosPendientesSeleccionados = [];
            session()->put('visita_carcel.comparacion', $this->coincidenciasCruce);
            session()->put('visita_carcel.tipo_comparacion', 'simple');
            session()->forget(['visita_carcel.causas_pendientes', 'visita_carcel.imputados_pendientes']);
            $this->reset('archivoMovimientos');
            session()->flash('ok', 'Comparacion completada. Los resultados son temporales y no se guardaron en el catalogo.');
        } catch (\RuntimeException $exception) {
            $this->addError('archivoMovimientos', $exception->getMessage());
        } catch (\Throwable $exception) {
            report($exception);
            $this->addError('archivoMovimientos', 'No fue posible procesar la planilla. Verifique que el archivo no esté dañado y vuelva a intentarlo.');
        }
    }

    private function importarLibroVisitaCarcel(SafeSpreadsheetReader $reader): void
    {
        try {
            $spreadsheet = $reader->loadAll($this->archivoMovimientos->getRealPath(), true, 5000, 70);
            $result = ['hojas_procesadas' => 0, 'hojas_omitidas' => 0, 'filas_procesadas' => 0, 'movimientos_creados' => 0, 'causas_no_encontradas' => 0, 'imputados_no_encontrados' => 0, 'coincidencias_ambiguas' => 0, 'duplicados' => 0, 'inconsistencias' => 0, 'errores' => []];
            $matches = [];
            $pending = [];
            $pendingImputados = [];
            $seen = [];
            $rucsPresentes = [];
            $lecturaIncompleta = false;

            foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
                $rows = $sheet->toArray(null, true, false, true);
                $header = $this->encabezadosLibroComparacion($rows);
                if ($header === null) {
                    $lecturaIncompleta = true;
                    $result['hojas_omitidas']++;
                    $this->agregarErrorImportacion($result, 0, "hoja {$sheet->getTitle()}: no se encontraron las columnas del libro Visita Cárcel.");

                    continue;
                }

                [$headers, $headerRow] = $header;
                $penal = $this->penalPlanilla([], 0, $sheet->getTitle());
                $result['hojas_procesadas']++;

                foreach (array_slice($rows, $headerRow + 1) as $index => $row) {
                    $data = $this->datosLibroComparacion($this->rowData($headers, $row));
                    if ($this->rowIsEmpty($data)) {
                        continue;
                    }

                    $result['filas_procesadas']++;
                    $fila = $headerRow + $index + 2;
                    $ruc = trim((string) $data['ruc']);
                    if ($ruc !== '') {
                        $rucsPresentes[$ruc] = true;
                    } else {
                        $lecturaIncompleta = true;
                    }
                    $rit = trim((string) $data['rit']).'-'.trim((string) $data['ano']);
                    $rut = $this->normalizarRut((string) $data['rut']);
                    $fingerprint = hash('sha256', implode('|', [$ruc, $rit, $rut ?: $this->normalizarTexto((string) $data['imputado'])]));

                    if ($ruc === '' || $rit === '-') {
                        $result['inconsistencias']++;
                        $this->agregarErrorImportacion($result, $fila, "hoja {$sheet->getTitle()}: faltan RUC, RIT o AÑO.");

                        continue;
                    }

                    if (isset($seen[$fingerprint])) {
                        $result['duplicados']++;
                        $this->agregarErrorImportacion($result, $fila, "hoja {$sheet->getTitle()}: registro duplicado.");

                        continue;
                    }

                    $seen[$fingerprint] = true;

                    $causa = VisitaCarcelCausa::query()->with('imputados')->where('ruc', $ruc)->first();
                    if (! $causa) {
                        $candidate = $this->causaPendienteDesdeLibro($data, $ruc, $rit, $penal, $sheet->getTitle(), $fila);
                        $pending[$ruc] ??= ['ruc' => $ruc, 'rit' => $rit, 'hojas' => [], 'imputados' => []];
                        $pending[$ruc]['hojas'][] = $sheet->getTitle();
                        $pending[$ruc]['imputados'][] = $candidate;

                        continue;
                    }

                    if ($causa->rit !== $rit) {
                        $result['inconsistencias']++;
                        $this->agregarErrorImportacion($result, $fila, "hoja {$sheet->getTitle()}: el RIT {$rit} no coincide con el catálogo.");

                        continue;
                    }

                    $imputado = $this->imputadoParaMovimiento($causa, (string) $data['rut'], (string) $data['imputado']);
                    if ($imputado === false) {
                        $result['coincidencias_ambiguas']++;
                        $this->agregarErrorImportacion($result, $fila, "hoja {$sheet->getTitle()}: el imputado es ambiguo.");

                        continue;
                    }

                    if ($imputado === null) {
                        $result['imputados_no_encontrados']++;
                        $pendingImputados[$fingerprint] = $this->causaPendienteDesdeLibro($data, $ruc, $rit, $penal, $sheet->getTitle(), $fila) + [
                            'id' => $fingerprint,
                            'causa_id' => $causa->id,
                        ];

                        continue;
                    }

                    $matches[$fingerprint] = ['ruc' => $ruc, 'rit' => $rit, 'anio' => trim((string) $data['ano']), 'causa_id' => $causa->id, 'nombre' => $imputado->nombre, 'rut' => $imputado->rut, 'hoja' => $sheet->getTitle(), 'estado' => 'Encontrado'];
                    $result['movimientos_creados']++;
                }
            }

            $this->causasPendientes = collect($pending)->map(function (array $causa): array {
                $causa['hojas'] = array_values(array_unique($causa['hojas']));

                return $causa;
            })->values()->all();
            $this->causasPendientesSeleccionadas = collect($this->causasPendientes)->pluck('ruc')->all();
            $this->imputadosPendientes = array_values($pendingImputados);
            $this->imputadosPendientesSeleccionados = collect($this->imputadosPendientes)->filter(fn (array $imputado) => $imputado['valido'])->pluck('id')->all();
            $result['causas_no_encontradas'] = count($this->causasPendientes);
            $this->resultadoImportacion = $result;
            $this->compararCausasAusentes($rucsPresentes, $lecturaIncompleta);
            $this->coincidenciasCruce = array_values(array_filter($matches, 'is_array'));
            session()->put('visita_carcel.comparacion', $this->coincidenciasCruce);
            session()->put('visita_carcel.tipo_comparacion', 'libro');
            session()->put('visita_carcel.causas_pendientes', $this->causasPendientes);
            session()->put('visita_carcel.imputados_pendientes', $this->imputadosPendientes);
            $this->reset('archivoMovimientos');
            session()->flash('ok', 'Comparación del libro completada. No se realizaron cambios en el catálogo.');
        } catch (\RuntimeException $exception) {
            $this->addError('archivoMovimientos', $exception->getMessage());
        } catch (\Throwable $exception) {
            report($exception);
            $this->addError('archivoMovimientos', 'No fue posible procesar el libro. Verifique que el archivo no esté dañado y vuelva a intentarlo.');
        }
    }

    public function salirModoCruce(): void
    {
        $this->reset(['coincidenciasCruce', 'resultadoImportacion', 'causasPendientes', 'causasPendientesSeleccionadas', 'imputadosPendientes', 'imputadosPendientesSeleccionados', 'causasAusentesIds', 'comparacionAusenciasDisponible', 'avisoComparacionAusencias', 'revisionCausasAusentesAbierta']);
        $this->resetPage('ausentesPage');
        session()->forget(['visita_carcel.comparacion', 'visita_carcel.tipo_comparacion', 'visita_carcel.causas_pendientes', 'visita_carcel.imputados_pendientes']);
    }

    private function compararCausasAusentes(array $rucsPresentes, bool $lecturaIncompleta): void
    {
        if ($lecturaIncompleta || $rucsPresentes === []) {
            $this->avisoComparacionAusencias = 'No se determinaron causas ausentes: el archivo está vacío, contiene filas sin RUC o tiene hojas que no pudieron compararse. Corrige el archivo y vuelve a importar.';

            return;
        }

        $this->causasAusentesIds = VisitaCarcelCausa::query()
            ->whereNotIn('ruc', array_keys($rucsPresentes))
            ->pluck('id')->all();
        $this->comparacionAusenciasDisponible = true;
    }

    public function abrirRevisionCausasAusentes(): void
    {
        Gate::authorize('visita_carcel.ver');

        $this->revisionCausasAusentesAbierta = $this->comparacionAusenciasDisponible;
    }

    public function cerrarRevisionCausasAusentes(): void
    {
        $this->revisionCausasAusentesAbierta = false;
    }

    public function eliminarCausaAusente(int $causaId): void
    {
        Gate::authorize('visita_carcel.gestionar');
        abort_unless($this->comparacionAusenciasDisponible && in_array($causaId, $this->causasAusentesIds, true), 422);

        $this->eliminarCausa($causaId);
        $this->resetPage('ausentesPage');
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
        $this->revisionCausasAusentesAbierta = false;
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
            'penal' => ['required', Rule::in($this->nombresPenalesActivos())],
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

        $imputado = VisitaCarcelImputado::query()->findOrFail($this->imputadoEditandoId);
        $penales = $this->nombresPenalesActivos();
        if (! in_array($imputado->penal, $penales, true)) {
            $penales[] = $imputado->penal;
        }

        $validated = $this->validate([
            'imputadoEdicion.nombre' => ['required', 'string', 'max:255'],
            'imputadoEdicion.rut' => ['required', 'string', 'max:20'],
            'imputadoEdicion.penal' => ['required', Rule::in($penales)],
            'imputadoEdicion.delito' => ['required', 'string'],
            'imputadoEdicion.fecha_ingreso_pp' => ['required', 'date'],
            'imputadoEdicion.plazo_cierre' => ['nullable', 'date'],
            'imputadoEdicion.ultima_rev_pp' => ['nullable', 'date'],
            'imputadoEdicion.juez' => ['nullable', 'string', 'max:255'],
            'imputadoEdicion.defensor' => ['nullable', 'string', 'max:255'],
            'imputadoEdicion.fecha_ult_res' => ['nullable', 'date'],
            'imputadoEdicion.ult_resolucion' => ['nullable', 'string'],
        ]);

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

    public function restaurarImputado(int $imputadoId): void
    {
        Gate::authorize('visita_carcel.gestionar');

        DB::transaction(function () use ($imputadoId): void {
            $imputado = VisitaCarcelImputado::query()->withTrashed()->findOrFail($imputadoId);

            if (! $imputado->trashed()) {
                return;
            }

            $causa = VisitaCarcelCausa::query()->withTrashed()->findOrFail($imputado->visita_carcel_causa_id);
            if ($causa->trashed()) {
                $causa->restore();
                $causa->update(['updated_by' => Auth::id()]);
            }

            $imputado->restore();
            $imputado->update(['updated_by' => Auth::id()]);
        });

        $this->dispatch('app-toast', message: 'Imputado restaurado correctamente.');
    }

    public function restaurarCausa(int $causaId): void
    {
        Gate::authorize('visita_carcel.gestionar');

        DB::transaction(function () use ($causaId): void {
            $causa = VisitaCarcelCausa::query()->withTrashed()->findOrFail($causaId);
            $imputados = $causa->imputados()->withTrashed()->get();

            if ($causa->trashed()) {
                $causa->restore();
                $causa->update(['updated_by' => Auth::id()]);
            }

            foreach ($imputados->filter->trashed() as $imputado) {
                $imputado->restore();
                $imputado->update(['updated_by' => Auth::id()]);
            }
        });

        $this->dispatch('app-toast', message: 'Causa e imputados asociados restaurados correctamente.');
    }

    private function reporteControl(): array
    {
        $desdeValor = validator(['date' => $this->reporteDesde], ['date' => ['required', 'date_format:Y-m-d']])->passes()
            ? $this->reporteDesde : now('America/Santiago')->startOfMonth()->toDateString();
        $hastaValor = validator(['date' => $this->reporteHasta], ['date' => ['required', 'date_format:Y-m-d']])->passes()
            ? $this->reporteHasta : now('America/Santiago')->toDateString();
        if ($desdeValor > $hastaValor) {
            $desdeValor = now('America/Santiago')->startOfMonth()->toDateString();
            $hastaValor = now('America/Santiago')->toDateString();
        }
        $desde = Carbon::parse($desdeValor, 'America/Santiago')->startOfDay();
        $hasta = Carbon::parse($hastaValor, 'America/Santiago')->endOfDay();

        $agregados = VisitaCarcelCausa::query()->withTrashed()->whereBetween('created_at', [$desde, $hasta])->get()
            ->map(fn (VisitaCarcelCausa $causa) => ['tipo' => 'Causa', 'descripcion' => "RUC {$causa->ruc} · RIT {$causa->rit}", 'fecha' => $causa->created_at])
            ->concat(VisitaCarcelImputado::query()->withTrashed()->with('causa')->whereBetween('created_at', [$desde, $hasta])->get()
                ->map(fn (VisitaCarcelImputado $imputado) => ['tipo' => 'Imputado', 'descripcion' => "{$imputado->nombre} · {$imputado->rut}", 'fecha' => $imputado->created_at]))
            ->sortByDesc('fecha')->values();

        $eliminados = VisitaCarcelCausa::query()->withTrashed()->whereBetween('deleted_at', [$desde, $hasta])->get()
            ->map(fn (VisitaCarcelCausa $causa) => ['tipo' => 'Causa', 'id' => $causa->id, 'descripcion' => "RUC {$causa->ruc} · RIT {$causa->rit}", 'fecha' => $causa->deleted_at])
            ->concat(VisitaCarcelImputado::query()->withTrashed()->with('causa')->whereBetween('deleted_at', [$desde, $hasta])->get()
                ->map(fn (VisitaCarcelImputado $imputado) => ['tipo' => 'Imputado', 'id' => $imputado->id, 'descripcion' => "{$imputado->nombre} · {$imputado->rut}", 'fecha' => $imputado->deleted_at]))
            ->sortByDesc('fecha')->values();

        $modificados = VisitaCarcelImputadoHistorial::query()->with('imputado')->whereBetween('created_at', [$desde, $hasta])->get()
            ->map(fn (VisitaCarcelImputadoHistorial $historial) => ['descripcion' => "{$historial->imputado->nombre} · {$historial->archivo}", 'fecha' => $historial->created_at])
            ->sortByDesc('fecha')->values();

        return [
            'agregados' => $agregados,
            'eliminados' => $eliminados,
            'modificados' => $modificados,
            'por_penal' => VisitaCarcelImputado::query()->select('penal', DB::raw('COUNT(*) as total'))->groupBy('penal')->orderBy('penal')->get(),
            'total_imputados' => VisitaCarcelImputado::query()->count(),
        ];
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

                $bytesExtraidos = stream_copy_to_stream($origen, $salida, 20 * 1024 * 1024 + 1);
                fclose($origen);
                fclose($salida);
                if ($bytesExtraidos === false || $bytesExtraidos > 20 * 1024 * 1024) {
                    $resultados[] = $this->resultadoArchivoInvalido($entrada['nombre'], 'El ZIP contiene una planilla no permitida.');

                    continue;
                }
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

    public function abrirRevisionCausasPendientes(): void
    {
        Gate::authorize('visita_carcel.gestionar');

        $this->revisionCausasPendientesAbierta = $this->causasPendientes !== [];
    }

    public function asignarPenalPendiente(string $tipo, int $indice, int $subindice, string $penalId): void
    {
        Gate::authorize('visita_carcel.gestionar');
        abort_unless(in_array($tipo, ['causa', 'imputado'], true), 422);

        $clave = $tipo === 'causa' ? 'causas_pendientes' : 'imputados_pendientes';
        $pendientes = session('visita_carcel.'.$clave, []);
        $datos = $tipo === 'causa'
            ? ($pendientes[$indice]['imputados'][$subindice] ?? null)
            : ($pendientes[$indice] ?? null);
        abort_unless($datos && ($datos['penal_manual'] ?? blank($datos['campos']['penal'] ?? null)), 422);

        $campo = "penalPendiente.{$tipo}.{$indice}.{$subindice}";
        $this->resetValidation($campo);
        $penal = Penal::query()->where('activo', true)->find($penalId);
        if (! $penal) {
            $this->addError($campo, 'Seleccione un penal activo.');

            return;
        }

        $datos['penal_manual'] = true;
        $datos['penal'] = $penal->nombre;
        $datos['campos']['penal'] = $penal->nombre;
        $datos = $this->validarDatosPendientes($datos);

        if ($tipo === 'causa') {
            $pendientes[$indice]['imputados'][$subindice] = $datos;
            $this->causasPendientes = $pendientes;
        } else {
            $pendientes[$indice] = $datos;
            $this->imputadosPendientes = $pendientes;
        }

        session()->put('visita_carcel.'.$clave, $pendientes);
    }

    public function abrirRevisionImputadosPendientes(): void
    {
        Gate::authorize('visita_carcel.gestionar');

        $this->revisionImputadosPendientesAbierta = $this->imputadosPendientes !== [];
    }

    public function cerrarRevisionCausasPendientes(): void
    {
        $this->revisionCausasPendientesAbierta = false;
        $this->revisionImputadosPendientesAbierta = false;
    }

    public function crearCausasPendientes(): void
    {
        Gate::authorize('visita_carcel.gestionar');

        $this->validate(['causasPendientesSeleccionadas' => ['array', 'max:5000'], 'causasPendientesSeleccionadas.*' => ['string', 'max:255']]);

        $seleccionadas = array_flip($this->causasPendientesSeleccionadas);
        $pendientes = collect(session('visita_carcel.causas_pendientes', $this->causasPendientes))
            ->filter(fn (array $causa) => isset($seleccionadas[$causa['ruc']]))
            ->map(function (array $causa): array {
                $causa['imputados'] = array_map($this->validarDatosPendientes(...), $causa['imputados']);

                return $causa;
            });

        if ($pendientes->isEmpty()) {
            $this->addError('causasPendientesSeleccionadas', 'Seleccione al menos una causa para ingresar.');

            return;
        }

        $creadas = 0;
        $imputadosCreados = 0;
        $omitidas = 0;
        $resueltas = [];

        foreach ($pendientes as $pendiente) {
            if (! collect($pendiente['imputados'])->contains('valido', true)) {
                $omitidas++;

                continue;
            }

            DB::transaction(function () use ($pendiente, &$creadas, &$imputadosCreados, &$omitidas, &$resueltas): void {
                if (VisitaCarcelCausa::query()->where('ruc', $pendiente['ruc'])->exists()) {
                    $omitidas++;
                    $resueltas[] = $pendiente['ruc'];

                    return;
                }

                $causa = VisitaCarcelCausa::create([
                    'ruc' => $pendiente['ruc'],
                    'rit' => $pendiente['rit'],
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id(),
                ]);
                $creadas++;
                $resueltas[] = $pendiente['ruc'];

                foreach ($pendiente['imputados'] as $datos) {
                    if (! $datos['valido']) {
                        continue;
                    }

                    $imputado = $causa->imputados()->create($datos['campos'] + [
                        'created_by' => Auth::id(),
                        'updated_by' => Auth::id(),
                    ]);
                    VisitaCarcelImputadoHistorial::create([
                        'visita_carcel_imputado_id' => $imputado->id,
                        'user_id' => Auth::id(),
                        'archivo' => 'Comparación libro Visita Cárcel',
                        'cambios' => collect($datos['campos'])->map(fn ($valor) => ['anterior' => null, 'nuevo' => $valor])->all(),
                    ]);
                    $this->registrarIngresoEnComparacion($causa, $imputado, $datos['hoja']);
                    $imputadosCreados++;
                }
            });
        }

        $this->causasPendientes = collect($this->causasPendientes)->reject(fn (array $causa) => in_array($causa['ruc'], $resueltas, true))->values()->all();
        $this->causasPendientesSeleccionadas = collect($this->causasPendientes)->pluck('ruc')->all();
        session()->put('visita_carcel.causas_pendientes', $this->causasPendientes);
        $this->resultadoImportacion['causas_no_encontradas'] = count($this->causasPendientes);
        if ($creadas > 0) {
            $this->resetPage();
        }
        $this->revisionCausasPendientesAbierta = false;
        session()->flash('ok', "Se ingresaron {$creadas} causas y {$imputadosCreados} imputados. {$omitidas} causas fueron omitidas.");
    }

    public function crearImputadosPendientes(): void
    {
        Gate::authorize('visita_carcel.gestionar');

        $this->validate(['imputadosPendientesSeleccionados' => ['array', 'max:5000'], 'imputadosPendientesSeleccionados.*' => ['string', 'max:255']]);

        $seleccionados = array_flip($this->imputadosPendientesSeleccionados);
        $pendientes = collect(session('visita_carcel.imputados_pendientes', $this->imputadosPendientes))
            ->filter(fn (array $imputado) => isset($seleccionados[$imputado['id']]))
            ->map($this->validarDatosPendientes(...));

        if ($pendientes->isEmpty()) {
            $this->addError('imputadosPendientesSeleccionados', 'Seleccione al menos un imputado para ingresar.');

            return;
        }

        $creados = 0;
        $omitidos = 0;
        $resueltos = [];

        foreach ($pendientes as $pendiente) {
            if (! $pendiente['valido'] || ! in_array($pendiente['campos']['penal'], $this->nombresPenalesActivos(), true)) {
                $omitidos++;

                continue;
            }

            DB::transaction(function () use ($pendiente, &$creados, &$omitidos, &$resueltos): void {
                $causa = VisitaCarcelCausa::query()->with('imputados')->find($pendiente['causa_id']);
                if (! $causa || $causa->ruc !== $pendiente['ruc'] || $causa->rit !== $pendiente['rit']) {
                    $omitidos++;

                    return;
                }

                if ($this->imputadoParaMovimiento($causa, $pendiente['campos']['rut'], $pendiente['campos']['nombre']) !== null) {
                    $omitidos++;
                    $resueltos[] = $pendiente['id'];

                    return;
                }

                $imputado = $causa->imputados()->create($pendiente['campos'] + ['created_by' => Auth::id(), 'updated_by' => Auth::id()]);
                VisitaCarcelImputadoHistorial::create([
                    'visita_carcel_imputado_id' => $imputado->id,
                    'user_id' => Auth::id(),
                    'archivo' => 'Comparación libro Visita Cárcel',
                    'cambios' => collect($pendiente['campos'])->map(fn ($valor) => ['anterior' => null, 'nuevo' => $valor])->all(),
                ]);
                $this->registrarIngresoEnComparacion($causa, $imputado, $pendiente['hoja']);
                $creados++;
                $resueltos[] = $pendiente['id'];
            });
        }

        $this->imputadosPendientes = collect($this->imputadosPendientes)->reject(fn (array $imputado) => in_array($imputado['id'], $resueltos, true))->values()->all();
        $this->imputadosPendientesSeleccionados = collect($this->imputadosPendientes)->filter(fn (array $imputado) => $imputado['valido'])->pluck('id')->all();
        session()->put('visita_carcel.imputados_pendientes', $this->imputadosPendientes);
        $this->resultadoImportacion['imputados_no_encontrados'] = count($this->imputadosPendientes);
        $this->revisionImputadosPendientesAbierta = false;
        session()->flash('ok', "Se ingresaron {$creados} imputados. {$omitidos} fueron omitidos.");
    }

    private function registrarIngresoEnComparacion(VisitaCarcelCausa $causa, VisitaCarcelImputado $imputado, string $hoja): void
    {
        $this->coincidenciasCruce[] = [
            'ruc' => $causa->ruc,
            'rit' => $causa->rit,
            'anio' => str($causa->rit)->afterLast('-')->toString(),
            'causa_id' => $causa->id,
            'nombre' => $imputado->nombre,
            'rut' => $imputado->rut,
            'hoja' => $hoja,
            'estado' => 'Encontrado',
        ];
        $this->resultadoImportacion['movimientos_creados'] = count($this->coincidenciasCruce);
        session()->put('visita_carcel.comparacion', $this->coincidenciasCruce);
    }

    private function encabezadosLibroComparacion(array $rows): ?array
    {
        $required = ['rit', 'ano', 'ruc', 'imputado', 'rutimputado'];

        foreach (array_slice($rows, 0, 10) as $indice => $fila) {
            $headers = $this->headers($fila);

            if (array_diff($required, $headers) === []) {
                return [$headers, $indice];
            }
        }

        return null;
    }

    private function datosLibroComparacion(array $data): array
    {
        return [
            'rit' => $data['rit'] ?? '', 'ano' => $data['ano'] ?? '', 'ruc' => $data['ruc'] ?? '',
            'imputado' => $data['imputado'] ?? '', 'rut' => $data['rutimputado'] ?? '', 'delito' => $data['delito'] ?? '',
            'fecha_ingreso_pp' => $this->parseExcelDate($data['fecingresopriprev'] ?? null),
            'plazo_cierre' => $this->parseExcelDate($data['plazocierre'] ?? null),
            'ultima_rev_pp' => $this->parseExcelDate($data['ultimarevpreven'] ?? null),
            'juez' => $data['juez'] ?? '', 'defensor' => $data['defensor'] ?? '',
            'fecha_ult_res' => $this->parseExcelDate($data['fecultresolucion'] ?? null),
            'ult_resolucion' => $data['ultresolucion'] ?? '',
        ];
    }

    private function causaPendienteDesdeLibro(array $data, string $ruc, string $rit, ?string $penal, string $hoja, int $fila): array
    {
        $campos = [
            'nombre' => trim((string) $data['imputado']), 'rut' => trim((string) $data['rut']), 'penal' => $penal,
            'delito' => trim((string) $data['delito']), 'fecha_ingreso_pp' => $data['fecha_ingreso_pp'],
            'plazo_cierre' => $data['plazo_cierre'], 'ultima_rev_pp' => $data['ultima_rev_pp'],
            'juez' => (trim((string) $data['juez']) ?: null), 'defensor' => (trim((string) $data['defensor']) ?: null),
            'fecha_ult_res' => $data['fecha_ult_res'], 'ult_resolucion' => (trim((string) $data['ult_resolucion']) ?: null),
        ];

        return $this->validarDatosPendientes(['ruc' => $ruc, 'rit' => $rit, 'nombre' => $campos['nombre'], 'rut' => $campos['rut'], 'penal' => $penal, 'penal_manual' => $penal === null, 'delito' => $campos['delito'], 'hoja' => $hoja, 'fila' => $fila, 'campos' => $campos]);
    }

    private function validarDatosPendientes(array $datos): array
    {
        $faltantes = collect(['nombre' => 'imputado', 'rut' => 'RUT', 'penal' => 'penal', 'delito' => 'delito', 'fecha_ingreso_pp' => 'fecha de ingreso PP'])
            ->filter(fn ($etiqueta, $campo) => blank($datos['campos'][$campo]))->values()->all();
        if (filled($datos['campos']['penal']) && ! in_array($datos['campos']['penal'], $this->nombresPenalesActivos(), true)) {
            $faltantes[] = 'penal activo';
        }
        $datos['valido'] = $faltantes === [];
        $datos['motivo'] = $faltantes === [] ? null : 'Falta: '.implode(', ', $faltantes).'.';

        return $datos;
    }

    private function penalPlanilla(array $rows, int $filaEncabezados, string $nombreHoja = ''): ?string
    {
        $penales = Penal::query()->where('activo', true)->get();
        $referencias = array_filter([
            $this->normalizarPenal($nombreHoja),
            $this->normalizarPenal(collect(array_slice($rows, 0, $filaEncabezados))->flatten()->implode(' ')),
        ]);

        foreach ($referencias as $referencia) {
            $penal = $penales->first(fn (Penal $penal) => $this->normalizarPenal($penal->nombre) === $referencia)
                ?? $penales->first(fn (Penal $penal) => str_contains($referencia, $this->normalizarPenal($penal->nombre)));

            if ($penal) {
                return $penal->nombre;
            }
        }

        return null;
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
        $tokens = preg_split('/[^A-Z0-9]+/', $this->normalizarTexto($penal)) ?: [];
        $tokens = array_filter($tokens, fn (string $token) => ! in_array($token, ['DE', 'DEL', 'LA', 'EL'], true));

        return implode('', array_map(fn (string $token) => match ($token) {
            'SANTIAGO' => 'STGO',
            'UNO', 'I' => '1',
            'DOS', 'II' => '2',
            'GENERAL' => 'GRAL',
            default => $token,
        }, $tokens));
    }

    private function nombresPenalesActivos(): array
    {
        return Penal::query()->where('activo', true)->orderBy('nombre')->pluck('nombre')->all();
    }

    private function normalizarTexto(string $texto): string
    {
        return preg_replace('/\s+/', ' ', strtr(mb_strtoupper(trim($texto)), ['Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ñ' => 'N'])) ?? '';
    }
}
