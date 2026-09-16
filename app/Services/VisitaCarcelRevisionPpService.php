<?php

namespace App\Services;

use App\Models\VisitaCarcelImputado;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;

class VisitaCarcelRevisionPpService
{
    public function recordsForDate(CarbonInterface $date): Collection
    {
        $date = $date->copy()->startOfDay();

        return VisitaCarcelImputado::query()
            ->with('causa')
            ->whereDate('ultima_rev_pp', '<=', $date->copy()->subDays(160)->toDateString())
            ->get()
            ->each(fn (VisitaCarcelImputado $imputado) => $imputado->setAttribute('dias_plazo', $imputado->ultima_rev_pp->diffInDays($date)))
            ->sortByDesc('dias_plazo')
            ->values();
    }
}
