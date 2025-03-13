<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LService
{
    protected $apiUrl;

    public function __construct()
    {
        $this->apiUrl = 'http://10.13.214.129:8082/api/Libertad/';
    }

    public function createLibertad($data)
    {
        try {
            $response = Http::post($this->apiUrl, $data);

            if ($response->successful()) {
                return $response->json();
            } else {
                Log::error('Error al crear Libertad en la API', ['status' => $response->status()]);
                return null;
            }
        } catch (\Exception $e) {
            Log::error('Excepción al crear Libertad en la API: ' . $e->getMessage());
            return null;
        }
    }
}



