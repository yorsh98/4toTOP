<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OService
{
    protected $apiUrl;

    public function __construct()
    {
        $this->apiUrl = 'http://127.0.0.1:8001/api/Oficio/';
    }

    public function createOficio($data)
    {
        try {
            $response = Http::post($this->apiUrl, $data);

            if ($response->successful()) {
                return $response->json();
            } else {
                Log::error('Error al crear Oficio en la API', ['status' => $response->status()]);
                return null;
            }
        } catch (\Exception $e) {
            Log::error('Excepción al crear Oficio en la API: ' . $e->getMessage());
            return null;
        }
    }
}