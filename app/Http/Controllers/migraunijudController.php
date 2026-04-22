<?php

namespace App\Http\Controllers;

use App\Services\BackofficeDocumentosCatalog;
use Illuminate\Support\Carbon;

class migraunijudController extends Controller
{
    public function index(BackofficeDocumentosCatalog $catalog)
    {
        $catalogData = $catalog->all();
        $updatedAtRaw = data_get($catalogData, 'meta.updated_at');
        $updatedAt = null;

        if (is_string($updatedAtRaw) && trim($updatedAtRaw) !== '') {
            try {
                $updatedAt = Carbon::parse($updatedAtRaw)->format('d-m-Y H:i');
            } catch (\Throwable) {
                $updatedAt = $updatedAtRaw;
            }
        }

        return view('migraunijud', [
            'tribunales' => $catalog->tribunales(),
            'updatedAt' => $updatedAt,
        ]);
    }
}
