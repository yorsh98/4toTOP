<?php

namespace App\Livewire;

use App\Models\Funcionario;
use App\Models\Insumo;
use App\Models\InsumoMovimiento;
use App\Models\InsumoSolicitudDetalle;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class InsumosReportesPage extends Component
{
    use WithPagination;

    #[Url(as: 'usuario', except: '')]
    public string $usuarioFiltro = '';

    #[Url(as: 'insumo', except: '')]
    public string $insumoFiltro = '';

    #[Url(as: 'desde', except: '')]
    public string $fechaDesde = '';

    #[Url(as: 'hasta', except: '')]
    public string $fechaHasta = '';

    #[Url(as: 'solicitud_desde', except: '')]
    public string $fechaSolicitudDesde = '';

    #[Url(as: 'solicitud_hasta', except: '')]
    public string $fechaSolicitudHasta = '';

    public function mount(): void
    {
        Gate::authorize('insumos.reportes');
    }

    public function updatedUsuarioFiltro(): void
    {
        $this->resetPage();
    }

    public function updatedInsumoFiltro(): void
    {
        $this->resetPage();
    }

    public function updatedFechaDesde(): void
    {
        $this->resetPage();
    }

    public function updatedFechaHasta(): void
    {
        $this->resetPage();
    }

    public function updatedFechaSolicitudDesde(): void
    {
        $this->resetPage();
    }

    public function updatedFechaSolicitudHasta(): void
    {
        $this->resetPage();
    }

    public function limpiarFiltros(): void
    {
        $this->reset('usuarioFiltro', 'insumoFiltro', 'fechaDesde', 'fechaHasta', 'fechaSolicitudDesde', 'fechaSolicitudHasta');
        $this->resetPage();
    }

    public function render()
    {
        Gate::authorize('insumos.reportes');

        $movimientos = $this->movimientosQuery();
        $solicitudDetalles = $this->solicitudDetallesQuery();

        return view('livewire.insumos-reportes-page', [
            'movimientos' => (clone $movimientos)
                ->with(['insumo', 'sector', 'asignadoA', 'asignadoFuncionario', 'createdBy'])
                ->latest('id')
                ->paginate(20),
            'totalesPorUsuario' => (clone $movimientos)
                ->whereNull('asignado_a_funcionario_id')
                ->selectRaw('asignado_a_user_id, SUM(cantidad) as cantidad_total, COUNT(*) as entregas_total')
                ->with('asignadoA:id,name,email')
                ->groupBy('asignado_a_user_id')
                ->orderByDesc('cantidad_total')
                ->get(),
            'totalesPorFuncionario' => (clone $movimientos)
                ->whereNotNull('asignado_a_funcionario_id')
                ->selectRaw('asignado_a_funcionario_id, SUM(cantidad) as cantidad_total, COUNT(*) as entregas_total')
                ->with('asignadoFuncionario:id,nombre,email')
                ->groupBy('asignado_a_funcionario_id')
                ->orderByDesc('cantidad_total')
                ->get(),
            'totalUnidades' => (int) (clone $movimientos)->sum('cantidad'),
            'totalEntregas' => (clone $movimientos)->count(),
            'totalSolicitado' => (int) (clone $solicitudDetalles)->sum('cantidad_solicitada'),
            'totalEntregadoSolicitudes' => (int) (clone $solicitudDetalles)
                ->join('insumo_movimientos', 'insumo_movimientos.solicitud_detalle_id', '=', 'insumo_solicitud_detalles.id')
                ->where('insumo_movimientos.tipo', 'asignacion')
                ->sum('insumo_movimientos.cantidad'),
            'totalPendiente' => (int) (clone $solicitudDetalles)
                ->whereHas('solicitud', fn ($query) => $query->whereIn('estado', ['pendiente', 'parcial']))
                ->sum('cantidad_solicitada') - (int) (clone $solicitudDetalles)
                ->whereHas('solicitud', fn ($query) => $query->whereIn('estado', ['pendiente', 'parcial']))
                ->join('insumo_movimientos', 'insumo_movimientos.solicitud_detalle_id', '=', 'insumo_solicitud_detalles.id')
                ->where('insumo_movimientos.tipo', 'asignacion')
                ->sum('insumo_movimientos.cantidad'),
            'funcionarios' => Funcionario::query()->orderBy('nombre')->get(['id', 'nombre']),
            'insumos' => Insumo::query()->orderBy('nombre')->get(['id', 'nombre', 'codigo']),
        ]);
    }

    private function fechaValida(string $fecha): bool
    {
        return $fecha !== '' && Validator::make(['fecha' => $fecha], ['fecha' => ['date_format:Y-m-d']])->passes();
    }

    private function movimientosQuery()
    {
        $funcionarioFiltroId = preg_match('/\Afuncionario:[1-9][0-9]*\z/', $this->usuarioFiltro)
            ? (int) substr($this->usuarioFiltro, strlen('funcionario:'))
            : null;
        $usuarioFiltroId = ctype_digit($this->usuarioFiltro) ? (int) $this->usuarioFiltro : null;

        return InsumoMovimiento::query()
            ->where('tipo', 'asignacion')
            ->when($funcionarioFiltroId, fn ($query) => $query->where('asignado_a_funcionario_id', $funcionarioFiltroId))
            ->when($usuarioFiltroId, fn ($query) => $query->where('asignado_a_user_id', $usuarioFiltroId))
            ->when(ctype_digit($this->insumoFiltro) && (int) $this->insumoFiltro > 0, fn ($query) => $query->where('insumo_id', (int) $this->insumoFiltro))
            ->when($this->fechaValida($this->fechaDesde), fn ($query) => $query->whereDate('created_at', '>=', $this->fechaDesde))
            ->when($this->fechaValida($this->fechaHasta), fn ($query) => $query->whereDate('created_at', '<=', $this->fechaHasta));
    }

    private function solicitudDetallesQuery()
    {
        $usuarioFiltroId = ctype_digit($this->usuarioFiltro) ? (int) $this->usuarioFiltro : null;

        return InsumoSolicitudDetalle::query()
            ->when(ctype_digit($this->insumoFiltro) && (int) $this->insumoFiltro > 0, fn ($query) => $query->where('insumo_solicitud_detalles.insumo_id', (int) $this->insumoFiltro))
            ->whereHas('solicitud', function ($query) use ($usuarioFiltroId): void {
                $query
                    ->when($usuarioFiltroId, fn ($query) => $query->where('solicitante_user_id', $usuarioFiltroId))
                    ->when($this->fechaValida($this->fechaSolicitudDesde), fn ($query) => $query->whereDate('created_at', '>=', $this->fechaSolicitudDesde))
                    ->when($this->fechaValida($this->fechaSolicitudHasta), fn ($query) => $query->whereDate('created_at', '<=', $this->fechaSolicitudHasta));
            });
    }
}
