<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LibertadesService
{
    protected $apiUrl;

    public function __construct()
    {
        $this->apiUrl = 'http://127.0.0.1:8001/api/Libertad/';
    }

    public function getLibertad()
    {
        try {
            $response = Http::get($this->apiUrl);

            if ($response->successful()) {
                $data = $response->json("libertad");

                return is_array($data) ? $data : [];
            } else {
                Log::error('Error en la API de libertad', ['status' => $response->status()]);
                return [];
            }
        } catch (\Exception $e) {
            Log::error('Excepción al obtener datos de la API: ' . $e->getMessage());
            return [];
        }
    }
}
