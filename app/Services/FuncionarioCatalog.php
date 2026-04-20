<?php

namespace App\Services;

use App\Models\Funcionario;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class FuncionarioCatalog
{
    public function roleOptions(): array
    {
        $roles = config('funcionarios.roles', []);

        return is_array($roles) ? $roles : [];
    }

    public function roles(): array
    {
        return array_keys($this->roleOptions());
    }

    public function rolesForContext(string $context): array
    {
        $contexts = config('funcionarios.contexts', []);

        if (! is_array($contexts)) {
            return [];
        }

        $roles = $contexts[$context] ?? [];

        return is_array($roles) ? $roles : [];
    }

    public function funcionariosByRoles(array $roles): Collection
    {
        return Funcionario::query()
            ->whereIn('rol', $roles)
            ->orderBy('nombre')
            ->get();
    }

    public function funcionariosByContext(string $context): Collection
    {
        return $this->funcionariosByRoles($this->rolesForContext($context));
    }

    public function nombresByRoles(array $roles): array
    {
        return $this->funcionariosByRoles($roles)
            ->pluck('nombre')
            ->values()
            ->all();
    }

    public function nombresByContext(string $context): array
    {
        return $this->nombresByRoles($this->rolesForContext($context));
    }

    public function emailsByRoles(array $roles): array
    {
        return $this->funcionariosByRoles($roles)
            ->pluck('email')
            ->unique()
            ->values()
            ->all();
    }

    public function emailsByContext(string $context): array
    {
        return $this->emailsByRoles($this->rolesForContext($context));
    }

    public function destinatariosByRoles(array $roles): array
    {
        return $this->funcionariosByRoles($roles)
            ->whereNotNull('email')
            ->unique('email')
            ->values()
            ->map(function (Funcionario $funcionario): array {
                return [
                    'nombre' => $funcionario->nombre,
                    'run' => $funcionario->run,
                    'email' => $funcionario->email,
                    'telefono' => $funcionario->telefono,
                    'rol' => $funcionario->rol,
                    'rol_label' => $funcionario->rol_label,
                ];
            })
            ->all();
    }

    public function destinatariosByContext(string $context): array
    {
        return $this->destinatariosByRoles($this->rolesForContext($context));
    }

    public function destinatarioByNombre(string $nombre): ?array
    {
        $nombre = trim($nombre);
        if ($nombre === '') {
            return null;
        }

        $funcionario = Funcionario::query()
            ->where('nombre', $nombre)
            ->whereNotNull('email')
            ->orderBy('id')
            ->first();

        if (! $funcionario) {
            return null;
        }

        return [
            'nombre' => $funcionario->nombre,
            'run' => $funcionario->run,
            'email' => $funcionario->email,
            'telefono' => $funcionario->telefono,
            'rol' => $funcionario->rol,
            'rol_label' => $funcionario->rol_label,
        ];
    }

    public function perfilByNombre(string $nombre): ?array
    {
        $nombre = trim($nombre);
        if ($nombre === '') {
            return null;
        }

        $funcionario = Funcionario::query()
            ->where('nombre', $nombre)
            ->orderBy('id')
            ->first();

        if (! $funcionario) {
            return null;
        }

        return [
            'nombre' => $funcionario->nombre,
            'run' => $funcionario->run,
            'email' => $funcionario->email,
            'telefono' => $funcionario->telefono,
            'rol' => $funcionario->rol,
            'rol_label' => $funcionario->rol_label,
        ];
    }

    public function cumpleanerosDelDia(CarbonInterface|string|null $date = null): array
    {
        $target = $date instanceof CarbonInterface ? $date : Carbon::parse($date ?? now());

        return Funcionario::query()
            ->whereMonth('fecha_cumpleanos', $target->month)
            ->whereDay('fecha_cumpleanos', $target->day)
            ->orderBy('nombre')
            ->get()
            ->map(function (Funcionario $funcionario): array {
                return [
                    'nombre' => $funcionario->nombre,
                    'email' => $funcionario->email,
                    'rol' => $funcionario->rol,
                    'rol_label' => $funcionario->rol_label,
                    'fecha_cumpleanos' => optional($funcionario->fecha_cumpleanos)->format('Y-m-d'),
                ];
            })
            ->all();
    }

    public function cumpleanerosDelMes(int|string $month): array
    {
        return Funcionario::query()
            ->whereMonth('fecha_cumpleanos', (int) $month)
            ->orderBy('fecha_cumpleanos')
            ->orderBy('nombre')
            ->get()
            ->map(function (Funcionario $funcionario): array {
                return [
                    'nombre' => $funcionario->nombre,
                    'email' => $funcionario->email,
                    'rol' => $funcionario->rol,
                    'rol_label' => $funcionario->rol_label,
                    'fecha_cumpleanos' => optional($funcionario->fecha_cumpleanos)->format('Y-m-d'),
                ];
            })
            ->all();
    }
}
