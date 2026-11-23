<?php

namespace App\Services\Servicios\Maqueta;

use App\Services\Servicios\Maqueta\Contracts\PlanoMurosRepository;
use DomainException;

class ArchivoPlanoMurosRepository implements PlanoMurosRepository
{
    public function muros(int $piso): array
    {
        $archivo = resource_path("planos/piso-{$piso}.php");
        if (! is_file($archivo)) {
            throw new DomainException("No existe un plano de muros para el piso {$piso}.");
        }

        return require $archivo;
    }
}
