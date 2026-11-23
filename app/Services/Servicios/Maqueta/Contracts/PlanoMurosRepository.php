<?php

namespace App\Services\Servicios\Maqueta\Contracts;

interface PlanoMurosRepository
{
    public function muros(int $piso): array;
}
